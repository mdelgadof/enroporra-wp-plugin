<?php
/**
 * Import players from a CSV file.
 *
 * Run with:
 *   wp eval-file wp-content/plugins/enroporra/scripts/import-players.php --allow-root
 *
 * The CSV must be at:
 *   wp-content/plugins/enroporra/scripts/players.csv
 *
 * CSV format (with header row):
 *   nombre,apellido,equipo,fotmob_id
 *
 * - nombre:     Nombre de pila (ej. "Alexander")
 * - apellido:   Apellido(s) (ej. "Sørloth")
 * - equipo:     Nombre del equipo en español, exactamente como está en WP (ej. "Noruega")
 * - fotmob_id:  ID numérico del jugador en FotMob (ej. "440330") — opcional, deja vacío si no lo tienes
 *
 * Examples:
 *   Alexander,Sørloth,Noruega,440330
 *   Kylian,Mbappé,Francia,231747
 *   Lionel,Messi,Argentina,
 *
 * The script will:
 *   - Skip players that already exist (same nombre+apellido+equipo)
 *   - Download the photo from FotMob if fotmob_id is provided
 *   - Link each player to the current competition
 *   - Mark as bet_scorer = true
 */

$csv_path = WP_CONTENT_DIR . '/plugins/enroporra/scripts/players.csv';

if (!file_exists($csv_path)) {
    echo "ERROR: No se encuentra el fichero CSV en:\n  $csv_path\n";
    echo "Créalo con el formato: nombre,apellido,equipo,fotmob_id\n";
    return;
}

// ── Build team name → EP_Team map ────────────────────────────────────────────
$team_posts = get_posts(['post_type' => 'team', 'posts_per_page' => -1, 'post_status' => 'publish']);
$team_map = []; // normalised name → post_id
foreach ($team_posts as $tp) {
    $team_map[ep_normalise_player_import($tp->post_title)] = $tp->ID;
}

// ── Load current competition ──────────────────────────────────────────────────
try {
    $competition = EP_Competition::getCurrentCompetition();
} catch (Exception $e) {
    echo "ERROR: No hay competición activa: " . $e->getMessage() . "\n";
    return;
}

// ── Parse CSV ────────────────────────────────────────────────────────────────
$handle = fopen($csv_path, 'r');
$header = fgetcsv($handle); // skip header row

$created  = 0;
$skipped  = 0;
$errors   = 0;
$row_num  = 1;

while (($row = fgetcsv($handle)) !== false) {
    $row_num++;
    if (count($row) < 3) {
        echo "WARN  [fila $row_num] Fila incompleta, ignorando: " . implode(',', $row) . "\n";
        continue;
    }

    $nombre    = trim($row[0]);
    $apellido  = trim($row[1]);
    $equipo    = trim($row[2]);
    $fotmob_id = isset($row[3]) ? trim($row[3]) : '';

    if ($nombre === '' || $apellido === '' || $equipo === '') {
        echo "WARN  [fila $row_num] Campos vacíos, ignorando.\n";
        continue;
    }

    // Find team
    $team_key = ep_normalise_player_import($equipo);
    if (!isset($team_map[$team_key])) {
        echo "ERROR [fila $row_num] Equipo no encontrado: \"$equipo\" (normalizado: \"$team_key\")\n";
        $errors++;
        continue;
    }

    try {
        $team = new EP_Team($team_map[$team_key]);
    } catch (Exception $e) {
        echo "ERROR [fila $row_num] No se pudo cargar el equipo \"$equipo\": " . $e->getMessage() . "\n";
        $errors++;
        continue;
    }

    // Build image URL
    $image_url = ($fotmob_id !== '') ? "https://images.fotmob.com/image_resources/playerimages/{$fotmob_id}.png" : null;

    // Create player
    try {
        $args = [
            'name'    => $nombre,
            'surname' => $apellido,
            'team'    => $team,
        ];
        if ($image_url) $args['image'] = $image_url;

        $player = EP_Player::createPlayer($args);
        $player->setCompetition($competition);
        $player->setBetScorer(true);

        $photo_status = $image_url ? ($player->getImage() ? '📷' : '⚠️ sin foto') : '(sin fotmob_id)';
        echo "OK    $nombre $apellido → $equipo  $photo_status\n";
        $created++;

    } catch (Exception $e) {
        $msg = $e->getMessage();
        if (strpos($msg, 'already exists') !== false) {
            echo "SKIP  $nombre $apellido ($equipo) — ya existe\n";
            $skipped++;
        } else {
            echo "ERROR [fila $row_num] $nombre $apellido: $msg\n";
            $errors++;
        }
    }
}

fclose($handle);

echo "\n────────────────────────────────\n";
echo "Creados:  $created\n";
echo "Saltados: $skipped (ya existían)\n";
echo "Errores:  $errors\n";

// ── Helper ────────────────────────────────────────────────────────────────────
function ep_normalise_player_import(string $s): string {
    $s = mb_strtolower($s, 'UTF-8');
    $from = ['á','à','ä','â','ã','å','é','è','ë','ê','í','ì','ï','î','ó','ò','ö','ô','õ','ø','ú','ù','ü','û','ý','ÿ','ñ','ç'];
    $to   = ['a','a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','o','u','u','u','u','y','y','n','c'];
    return trim(str_replace($from, $to, $s));
}
