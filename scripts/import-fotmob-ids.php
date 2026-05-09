<?php
/**
 * One-time import script: maps fotmob_id to each WP fixture for the World Cup 2026.
 * Run with: wp eval-file wp-content/plugins/enroporra/scripts/import-fotmob-ids.php
 *
 * Matching strategy: utcTime date (±5min) + normalised team names.
 * Unmatched fixtures are printed for manual review.
 */

$fotmob_fixtures = EP_FotmobClient::getLeagueFixtures(77);
if (!$fotmob_fixtures) {
    echo "ERROR: FotMob returned null. Check API key / network.\n";
    return;
}
echo "FotMob fixtures fetched: " . count($fotmob_fixtures) . "\n";

// Load all WP fixtures for current competition
$competition = EP_Competition::getCurrentCompetition();
$wp_fixtures  = $competition->getFixtures(); // returns EP_Fixture[]

$matched   = 0;
$skipped   = 0;
$unmatched = [];

foreach ($fotmob_fixtures as $fm) {
    $fm_utc   = strtotime($fm['status']['utcTime']);
    $fm_home  = ep_normalise_name($fm['home']['name']);
    $fm_away  = ep_normalise_name($fm['away']['name']);
    $fm_id    = $fm['id'];

    $found = null;
    foreach ($wp_fixtures as $wp) {
        $wp_utc  = strtotime($wp->getRawDate() . ' UTC');
        $wp_home = ep_normalise_name($wp->getTeam(1)->getName());
        $wp_away = ep_normalise_name($wp->getTeam(2)->getName());

        $time_match = abs($fm_utc - $wp_utc) <= 300; // ±5 min
        $team_match = ($fm_home === $wp_home && $fm_away === $wp_away)
                   || ($fm_home === $wp_away && $fm_away === $wp_home); // reversed

        if ($time_match && $team_match) {
            $found = $wp;
            break;
        }
    }

    if ($found) {
        $existing = get_post_meta($found->getId(), 'fotmob_id', true);
        if ($existing === (string)$fm_id) {
            $skipped++;
            continue;
        }
        update_post_meta($found->getId(), 'fotmob_id', (string)$fm_id);
        echo "OK  [{$fm_id}] {$fm['home']['name']} vs {$fm['away']['name']} → WP #{$found->getId()}\n";
        $matched++;
    } else {
        $unmatched[] = "[{$fm_id}] {$fm['home']['name']} vs {$fm['away']['name']} ({$fm['status']['utcTime']})";
    }
}

echo "\nResultado: $matched matched, $skipped already set, " . count($unmatched) . " unmatched.\n";
if ($unmatched) {
    echo "\nSin match (revisar manualmente):\n";
    foreach ($unmatched as $line) echo "  $line\n";
}

function ep_normalise_name(string $name): string {
    // Lowercase, remove accents, strip non-alpha
    $name = mb_strtolower($name);
    $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
    $name = preg_replace('/[^a-z0-9 ]/', '', $name);
    return trim(preg_replace('/\s+/', ' ', $name));
}
