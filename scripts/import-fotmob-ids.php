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
    $fm_home  = ep_fotmob_to_wp_name(ep_normalise_name($fm['home']['name']));
    $fm_away  = ep_fotmob_to_wp_name(ep_normalise_name($fm['away']['name']));
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

    // Fallback: date-only match for TBD knockout fixtures ("Por conocer")
    if (!$found) {
        foreach ($wp_fixtures as $wp) {
            $wp_utc  = strtotime($wp->getRawDate() . ' UTC');
            $wp_home = ep_normalise_name($wp->getTeam(1)->getName());
            if ($wp_home !== 'por conocer') continue; // only try TBD fixtures
            if (abs($fm_utc - $wp_utc) <= 300) {
                $found = $wp;
                break;
            }
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

/**
 * Maps FotMob English team names to their normalised WP Spanish equivalents.
 * Keys are normalised English names, values are normalised Spanish names.
 */
function ep_fotmob_to_wp_name(string $normalised_en): string {
    static $map = [
        // FotMob English (normalised) => WP Spanish (normalised)
        // Names that differ between FotMob English and WP Spanish:
        'south africa'           => 'sudafrica',
        'south korea'            => 'corea del sur',
        'czechia'                => 'rep checa',
        'bosnia and herzegovina' => 'bosnia y herzegovina',
        'usa'                    => 'ee uu',
        'qatar'                  => 'catar',
        'switzerland'            => 'suiza',
        'brazil'                 => 'brasil',
        'morocco'                => 'marruecos',
        'haiti'                  => 'haiti',
        'scotland'               => 'escocia',
        'turkiye'                => 'turquia',
        'germany'                => 'alemania',
        'netherlands'            => 'paises bajos',
        'japan'                  => 'japon',
        'ivory coast'            => 'costa de marfil',
        'sweden'                 => 'suecia',
        'tunisia'                => 'tunez',
        'spain'                  => 'espana',
        'cape verde'             => 'cabo verde',
        'belgium'                => 'belgica',
        'egypt'                  => 'egipto',
        'saudi arabia'           => 'arabia saudi',
        'iran'                   => 'iran',
        'new zealand'            => 'nueva zelanda',
        'france'                 => 'francia',
        'norway'                 => 'noruega',
        'algeria'                => 'argelia',
        'jordan'                 => 'jordania',
        'dr congo'               => 'rd congo',
        'england'                => 'inglaterra',
        'croatia'                => 'croacia',
        'panama'                 => 'panama',
        // Same in both languages (map to self for clarity):
        'mexico'                 => 'mexico',
        'canada'                 => 'canada',
        'curacao'                => 'curacao',
        'ecuador'                => 'ecuador',
        'ghana'                  => 'ghana',
        'argentina'              => 'argentina',
        'austria'                => 'austria',
        'portugal'               => 'portugal',
        'senegal'                => 'senegal',
        'iraq'                   => 'iraq',
        'colombia'               => 'colombia',
        'paraguay'               => 'paraguay',
        'australia'              => 'australia',
        'uruguay'                => 'uruguay',
        'uzbekistan'             => 'uzbekistan',
    ];
    return $map[$normalised_en] ?? $normalised_en;
}

function ep_normalise_name(string $name): string {
    // Lowercase
    $name = mb_strtolower($name, 'UTF-8');
    // Manual accent map (iconv TRANSLIT unreliable on this server)
    $from = ['á','à','ä','â','ã','å','é','è','ë','ê','í','ì','ï','î','ó','ò','ö','ô','õ','ø','ú','ù','ü','û','ý','ÿ','ñ','ç','ğ','ș','ț','ș','ă'];
    $to   = ['a','a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','o','u','u','u','u','y','y','n','c','g','s','t','s','a'];
    $name = str_replace($from, $to, $name);
    // Strip non-alpha-numeric (removes dots, hyphens, etc.)
    $name = preg_replace('/[^a-z0-9 ]/', '', $name);
    return trim(preg_replace('/\s+/', ' ', $name));
}
