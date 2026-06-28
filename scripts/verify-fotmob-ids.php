<?php
/**
 * Verification script: checks that fotmob_id on each knockout fixture matches
 * the correct teams on FotMob.
 *
 * Run with:
 *   wp eval-file wp-content/plugins/enroporra/scripts/verify-fotmob-ids.php
 */

$fotmob_fixtures = EP_FotmobClient::getLeagueFixtures(77);
if (!$fotmob_fixtures) {
    echo "ERROR: FotMob returned null. Check network.\n";
    return;
}

// Index FotMob fixtures by ID for O(1) lookup
$fm_by_id = [];
foreach ($fotmob_fixtures as $fm) {
    $fm_by_id[(string)$fm['id']] = $fm;
}

$competition = EP_Competition::getCurrentCompetition();
$wp_fixtures  = $competition->getFixtures();

$ok      = 0;
$errors  = [];
$missing = [];

foreach ($wp_fixtures as $fixture) {
    if ($fixture->getTournament() === 'groups') continue;

    $fotmob_id = get_post_meta($fixture->getId(), 'fotmob_id', true);
    $wp_home   = $fixture->getTeam(1)->getName();
    $wp_away   = $fixture->getTeam(2)->getName();
    $label     = sprintf("WP #%d %-30s  fotmob_id=%s", $fixture->getId(), "({$wp_home} vs {$wp_away})", $fotmob_id);

    if (!$fotmob_id) {
        $missing[] = $label . "  ← SIN fotmob_id";
        continue;
    }

    if (!isset($fm_by_id[$fotmob_id])) {
        $errors[] = $label . "  ← ID NO encontrado en FotMob";
        continue;
    }

    $fm       = $fm_by_id[$fotmob_id];
    $fm_home  = ep_fotmob_to_wp_name(ep_normalise_name($fm['home']['name']));
    $fm_away  = ep_fotmob_to_wp_name(ep_normalise_name($fm['away']['name']));
    $db_home  = ep_normalise_name($wp_home);
    $db_away  = ep_normalise_name($wp_away);

    $match = ($fm_home === $db_home && $fm_away === $db_away)
          || ($fm_home === $db_away && $fm_away === $db_home); // reversed order

    if ($match) {
        echo "OK  $label\n";
        $ok++;
    } else {
        $errors[] = $label . "\n"
                  . "        FotMob: {$fm['home']['name']} vs {$fm['away']['name']}\n"
                  . "        DB:     {$wp_home} vs {$wp_away}";
    }
}

echo "\n--- Resumen ---\n";
echo "OK: $ok\n";
if ($errors) {
    echo "ERRORES (" . count($errors) . "):\n";
    foreach ($errors as $e) echo "  ✗ $e\n";
}
if ($missing) {
    echo "SIN fotmob_id (" . count($missing) . "):\n";
    foreach ($missing as $m) echo "  ! $m\n";
}
if (!$errors && !$missing) {
    echo "Todo correcto.\n";
}

function ep_fotmob_to_wp_name(string $normalised_en): string {
    static $map = [
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
    $name = mb_strtolower($name, 'UTF-8');
    $from = ['á','à','ä','â','ã','å','é','è','ë','ê','í','ì','ï','î','ó','ò','ö','ô','õ','ø','ú','ù','ü','û','ý','ÿ','ñ','ç','ğ','ș','ț','ș','ă'];
    $to   = ['a','a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','o','u','u','u','u','y','y','n','c','g','s','t','s','a'];
    $name = str_replace($from, $to, $name);
    $name = preg_replace('/[^a-z0-9 ]/', '', $name);
    return trim(preg_replace('/\s+/', ' ', $name));
}
