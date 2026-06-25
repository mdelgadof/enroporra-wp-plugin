<?php

class EP_LiveScore {

    /**
     * Main entry point called by the REST cron endpoint.
     * Returns diagnostic array ['checked'=>N, 'updated'=>N, 'closed'=>N].
     */
    public static function run(): array {
        $stats = ['checked' => 0, 'updated' => 0, 'closed' => 0];

        $fixtures = self::getLiveFixtureCandidates();
        if (empty($fixtures)) return $stats;

        foreach ($fixtures as $fixture) {
            if ($fixture->isPlayed()) continue; // closed manually while cron was running

            $fotmob_id = get_post_meta($fixture->getId(), 'fotmob_id', true);
            if (!$fotmob_id) continue;

            $stats['checked']++;

            $match = EP_FotmobClient::getMatchScore($fotmob_id);
            if ($match === null) {
                error_log('EP_LiveScore: API error for fixture ' . $fixture->getId() . ' fotmob_id=' . $fotmob_id);
                continue;
            }
            if ($match === false) {
                // FotMob archiva los partidos del día anterior a medianoche UTC aunque sigan en curso.
                // Ignoramos el archivo si no han pasado 95 minutos desde el kickoff.
                $kickoff        = strtotime($fixture->getRawDate() . ' UTC');
                $elapsed_min    = (time() - $kickoff) / 60;
                if ($elapsed_min < 95) {
                    error_log('EP_LiveScore: FotMob archived fixture ' . $fixture->getId() . ' prematurely (' . (int)$elapsed_min . ' min elapsed), skipping');
                    continue;
                }
                error_log('EP_LiveScore: FotMob archived fixture ' . $fixture->getId() . ', reconstructing and closing');
                $synthetic = self::buildMatchFromArchivedData($fixture, $fotmob_id);
                if ($synthetic !== null) {
                    self::closeMatch($fixture, $synthetic);
                    $stats['closed']++;
                }
                continue;
            }

            $status = $match['status'] ?? [];

            if (!empty($status['finished'])) {
                self::closeMatch($fixture, $match);
                $stats['closed']++;
            } elseif (!empty($status['started'])) {
                self::updateLive($fixture, $match);
                $stats['updated']++;
            }
            // not started yet → skip
        }

        return $stats;
    }

    /**
     * Returns all fixture objects with fotmob_id whose kickoff was in the last 3 hours,
     * regardless of played status. Callers decide what to do with played vs unplayed.
     */
    public static function getLiveFixtureCandidates(): array {
        global $wpdb;

        $now  = gmdate('Y-m-d H:i:s');
        $from = gmdate('Y-m-d H:i:s', time() - 10800);

        // Custom SQL avoids the multi-JOIN OR query WP_Query would generate.
        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT p.ID
             FROM {$wpdb->posts} p
             WHERE p.post_type = 'fixture'
               AND p.post_status IN ('publish','acf-disabled')
               AND (
                 (
                   EXISTS (SELECT 1 FROM {$wpdb->postmeta} WHERE post_id = p.ID AND meta_key = 'fotmob_id')
                   AND EXISTS (SELECT 1 FROM {$wpdb->postmeta} WHERE post_id = p.ID AND meta_key = 'date' AND meta_value BETWEEN %s AND %s)
                 )
                 OR EXISTS (SELECT 1 FROM {$wpdb->postmeta} WHERE post_id = p.ID AND meta_key = 'ep_test_live' AND meta_value = '1')
               )",
            $from,
            $now
        ));

        $fixtures = [];
        foreach ($ids as $post_id) {
            try {
                $fixtures[] = new EP_Fixture((int)$post_id);
            } catch (Exception $e) {
                error_log('EP_LiveScore: could not load fixture ' . $post_id . ': ' . $e->getMessage());
            }
        }
        return $fixtures;
    }

    private static function updateLive(EP_Fixture $fixture, array $match): void {
        $fixture->setGoals(1, (int)$match['home']['score'], true);
        $fixture->setGoals(2, (int)$match['away']['score'], true);

        $liveTime = $match['status']['liveTime']['short'] ?? '';
        update_post_meta($fixture->getId(), 'live_minute', EP_LiveScore::parseMinute($liveTime));
    }

    private static function closeMatch(EP_Fixture $fixture, array $match): void {
        $goals1 = (int)$match['home']['score'];
        $goals2 = (int)$match['away']['score'];

        $fixture->setGoals(1, $goals1, false);
        $fixture->setGoals(2, $goals2, false);

        if ($goals1 > $goals2)      $winner = '1';
        elseif ($goals1 < $goals2)  $winner = '2';
        elseif ($fixture->getTournament() === 'groups') $winner = 'X';
        else                         $winner = '1'; // fallback: should not happen

        $fixture->setWinner($winner);

        // Import scorers from FotMob before calculating points
        $fotmob_id = get_post_meta($fixture->getId(), 'fotmob_id', true);
        if ($fotmob_id && !str_starts_with($fotmob_id, 'TEST_') && empty(get_post_meta($fixture->getId(), 'goal'))) {
            self::importScorers($fixture, $fotmob_id);
        }

        // Clear live meta
        $id = $fixture->getId();
        delete_post_meta($id, 'live_goals_team1');
        delete_post_meta($id, 'live_goals_team2');
        delete_post_meta($id, 'live_minute');

        // Invalidate cached bet stats so they get recalculated on next page load
        delete_post_meta($id, 'scores');
        delete_post_meta($id, 'players');
        delete_post_meta($id, 'winners');
        delete_post_meta($id, 'total');
        delete_post_meta($id, 'result_ok');

        $competition = $fixture->getCompetition();
        foreach ($competition->getBets(false) as $bet) {
            try {
                $bet->setCompetition($competition);
                $bet->calculatePoints();
            } catch (Exception $e) {
                error_log('EP_LiveScore: calculatePoints failed for bet ' . $bet->getId() . ': ' . $e->getMessage());
            }
        }
    }

    /**
     * Cuando FotMob archiva un partido (match-score devuelve false), reconstruye
     * el array de partido necesario para closeMatch() a partir de los goal events.
     * Fallback: último marcador live conocido en post_meta.
     */
    private static function buildMatchFromArchivedData(EP_Fixture $fixture, string $fotmob_id): ?array {
        $id = $fixture->getId();

        if (!str_starts_with($fotmob_id, 'TEST_')) {
            $events = EP_FotmobClient::getMatchGoalEvents($fotmob_id);
            if ($events !== null) {
                // isHome en cada evento indica a qué equipo se acredita el gol (incluyendo OG)
                $home_goals = count(array_filter($events, fn($e) => !empty($e['isHome'])));
                $away_goals = count(array_filter($events, fn($e) => empty($e['isHome'])));
                error_log("EP_LiveScore: reconstructed score for fixture {$id}: {$home_goals}-{$away_goals} from " . count($events) . " goal events");
                return [
                    'home'   => ['score' => $home_goals, 'name' => ''],
                    'away'   => ['score' => $away_goals, 'name' => ''],
                    'status' => ['finished' => true, 'started' => true, 'liveTime' => ['short' => '']],
                ];
            }
        }

        // Fallback: último marcador live conocido
        $live_g1 = get_post_meta($id, 'live_goals_team1', true);
        $live_g2 = get_post_meta($id, 'live_goals_team2', true);
        if ($live_g1 !== '' && $live_g2 !== '') {
            error_log("EP_LiveScore: closing fixture {$id} with last live score {$live_g1}-{$live_g2} (goal events unavailable)");
            return [
                'home'   => ['score' => (int)$live_g1, 'name' => ''],
                'away'   => ['score' => (int)$live_g2, 'name' => ''],
                'status' => ['finished' => true, 'started' => true, 'liveTime' => ['short' => '']],
            ];
        }

        error_log("EP_LiveScore: cannot reconstruct score for fixture {$id}, no data available");
        return null;
    }

    public static function importScorers(EP_Fixture $fixture, string $fotmob_id): void {
        $events = EP_FotmobClient::getMatchGoalEvents($fotmob_id);
        if ($events === null) {
            error_log('EP_LiveScore: could not fetch goal events for fixture ' . $fixture->getId());
            return;
        }

        $competition = $fixture->getCompetition();

        foreach ($events as $event) {
            $fotmob_player_id = (int)($event['player']['id'] ?? 0);
            $fotmob_name      = trim($event['player']['name'] ?? '');
            $is_home          = !empty($event['isHome']);
            $is_own_goal      = !empty($event['ownGoal']);
            $minute           = (int)($event['time'] ?? 0);

            if (!$fotmob_player_id || $fotmob_name === '') continue;

            // Goal type: own goal, penalty, or normal
            if ($is_own_goal) {
                $type = 'og';
            } elseif (stripos($event['suffixKey'] ?? '', 'pen') !== false
                   || stripos($event['goalDescriptionKey'] ?? '', 'pen') !== false) {
                $type = 'p';
            } else {
                $type = '';
            }

            // FotMob's isHome on a goal event indicates which team the goal is CREDITED to
            // (i.e. which side's event list it appears in). For own goals this is the benefiting
            // team, which is the OPPOSITE team from the player who scored it.
            $team_for = $is_home ? 1 : 2;

            // Physical team the player belongs to: for own goals, the player is on the other side.
            $player_is_home = $is_own_goal ? !$is_home : $is_home;
            $player_team = $fixture->getTeam($player_is_home ? 1 : 2);

            try {
                $player = self::findOrCreatePlayer($fotmob_name, $fotmob_player_id, $player_team, $competition);
                $fixture->setScorer([
                    'player' => $player,
                    'for'    => $team_for,
                    'type'   => $type,
                    'minute' => $minute,
                ]);
            } catch (Exception $e) {
                error_log('EP_LiveScore: scorer import failed for "' . $fotmob_name . '": ' . $e->getMessage());
            }
        }
    }

    private static function findOrCreatePlayer(string $fotmob_name, int $fotmob_id, EP_Team $team, EP_Competition $competition): EP_Player {
        global $wpdb;

        // Fast exact lookup by fotmob_player_id (handles repeated matches)
        $post_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'fotmob_player_id' AND meta_value = %d LIMIT 1",
            $fotmob_id
        ));
        if ($post_id) {
            $player = new EP_Player((int)$post_id);
            $player->setCompetition($competition);
            return $player;
        }

        // Name similarity search among ALL players in the DB — catches players from previous
        // competitions who aren't yet registered in this one.
        $best       = null;
        $best_score = 0.0;
        foreach (EP_Player::getAllPlayers() as $player) {
            similar_text(mb_strtolower($fotmob_name), mb_strtolower($player->getName()), $pct);
            if ($pct > $best_score) {
                $best_score = $pct;
                $best       = $player;
            }
        }

        if ($best && $best_score >= 80.0) {
            $best->setFotmobId($fotmob_id);
            $best->setCompetition($competition); // no-op if already registered
            return $best;
        }

        // Create new player; split "Firstname Rest" on first space
        $parts   = explode(' ', $fotmob_name, 2);
        $name    = $parts[0];
        $surname = $parts[1] ?? '';

        $player = EP_Player::createPlayer([
            'name'    => $name,
            'surname' => $surname,
            'team'    => $team,
            'image'   => 'https://images.fotmob.com/image_resources/playerimages/' . $fotmob_id . '.png',
        ]);
        $player->setCompetition($competition);
        $player->setFotmobId($fotmob_id);

        return $player;
    }

    /**
     * Converts FotMob liveTime.short to the format stored in live_minute.
     * "87'" → "87"  |  "45+2'" → "45+2"  |  "HT" → "HT"  |  anything else → "?"
     * FotMob may wrap the apostrophe with U+200E (LTR marks) and use U+2019 (curly quote).
     */
    public static function parseMinute(string $short): string {
        if ($short === 'HT') return 'HT';
        // Normalise: strip U+200E (LTR marks) and replace U+2019 curly apostrophe with ASCII '
        $short = str_replace(["\u{200E}", "\u{2019}"], ['', "'"], $short);
        if (preg_match("/^(\d+(?:\+\d+)?)'$/", $short, $m)) return $m[1];
        return '?';
    }
}
