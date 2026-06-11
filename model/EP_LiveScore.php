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
                error_log('EP_LiveScore: null response for fixture ' . $fixture->getId() . ' fotmob_id=' . $fotmob_id);
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
        $query = new WP_Query([
            'post_type'      => 'fixture',
            'posts_per_page' => 15,
            'fields'         => 'ids',
            'meta_query'     => [
                'relation' => 'OR',
                // Normal: kickoff in the last 3 hours with a fotmob_id
                [
                    'relation' => 'AND',
                    [
                        'key'     => 'fotmob_id',
                        'compare' => 'EXISTS',
                    ],
                    [
                        'key'     => 'date',
                        'value'   => [
                            gmdate('Y-m-d H:i:s', time() - 10800),
                            gmdate('Y-m-d H:i:s'),
                        ],
                        'compare' => 'BETWEEN',
                        'type'    => 'DATETIME',
                    ],
                ],
                // Test override: fixtures explicitly marked as live for testing
                [
                    'key'     => 'ep_test_live',
                    'value'   => '1',
                ],
            ],
        ]);

        $fixtures = [];
        foreach ($query->posts as $post_id) {
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

        foreach ($fixture->getCompetition()->getBets(false) as $bet) {
            $bet->calculatePoints();
        }
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
