<?php

// ── Token generation on plugin activation ────────────────────────────────────
register_activation_hook(__FILE__, function() {
    if (!get_option('ep_live_score_token')) {
        update_option('ep_live_score_token', wp_generate_password(32, false));
    }
});

// ── REST endpoint: called by crontab every minute ────────────────────────────
add_action('rest_api_init', function() {
    register_rest_route('enroporra/v1', '/live-scores', [
        'methods'             => 'GET',
        'callback'            => 'ep_rest_live_scores',
        'permission_callback' => '__return_true',
    ]);
});

function ep_rest_live_scores(WP_REST_Request $request): WP_REST_Response {
    $token = $request->get_param('token');
    if ($token !== get_option('ep_live_score_token')) {
        return new WP_REST_Response(['error' => 'Unauthorized'], 401);
    }
    $stats = EP_LiveScore::run();
    return new WP_REST_Response($stats, 200);
}

// ── AJAX endpoint: called by frontend JS every 60s ───────────────────────────
add_action('wp_ajax_nopriv_ep_live_scores', 'ep_ajax_live_scores');
add_action('wp_ajax_ep_live_scores',        'ep_ajax_live_scores');

function ep_ajax_live_scores(): void {
    $fixtures = EP_LiveScore::getLiveFixtureCandidates();
    $data = [];
    foreach ($fixtures as $fixture) {
        $id = (string)$fixture->getId();
        if ($fixture->isPlayed()) {
            // Recently closed — tell frontend to update the card
            $data[$id] = [
                'goals1' => (int)$fixture->getGoals(1),
                'goals2' => (int)$fixture->getGoals(2),
                'minute' => null,
                'status' => 'finished',
            ];
        } else {
            $goals1 = $fixture->getGoals(1, true);
            $goals2 = $fixture->getGoals(2, true);
            $minute = $fixture->getLiveMinute();
            $data[$id] = [
                'goals1' => $goals1 !== '' ? (int)$goals1 : null,
                'goals2' => $goals2 !== '' ? (int)$goals2 : null,
                'minute' => $minute ?: null,
                'status' => 'live',
            ];
        }
    }
    wp_send_json($data);
}
