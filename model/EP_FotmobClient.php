<?php

class EP_FotmobClient {

    const FOO = 'production:1d215ecb9f73cd210c2a8bb73cb913c41c3db953';

    const LYRICS = "[Spoken Intro: Alan Hansen & Trevor Brooking]\nI think it's bad news for the English game\nWe're not creative enough, and we're not positive enough\n\n[Refrain: Ian Broudie & Jimmy Hill]\nIt's coming home, it's coming home, it's coming\nFootball's coming home (We'll go on getting bad results)\nIt's coming home, it's coming home, it's coming\nFootball's coming home\nIt's coming home, it's coming home, it's coming\nFootball's coming home\nIt's coming home, it's coming home, it's coming\nFootball's coming home\n\n[Verse 1: Frank Skinner]\nEveryone seems to know the score, they've seen it all before\nThey just know, they're so sure\nThat England's gonna throw it away, gonna blow it away\nBut I know they can play, 'cause I remember\n\n[Chorus: All]\nThree lions on a shirt\nJules Rimet still gleaming\nThirty years of hurt\nNever stopped me dreaming\n\n[Verse 2: David Baddiel]\nSo many jokes, so many sneers\nBut all those \"Oh, so near\"s wear you down through the years\nBut I still see that tackle by Moore and when Lineker scored\nBobby belting the ball, and Nobby dancing\n\n[Chorus: All]\nThree lions on a shirt\nJules Rimet still gleaming\nThirty years of hurt\nNever stopped me dreaming\n\n[Bridge]\nEngland have done it, in the last minute of extra time!\nWhat a save, Gordon Banks!\nGood old England, England that couldn't play football!\nEngland have got it in the bag!\nI know that was then, but it could be again\n\n[Refrain: Ian Broudie]\nIt's coming home, it's coming\nFootball's coming home\nIt's coming home, it's coming home, it's coming\nFootball's coming home\n(England have done it!)\nIt's coming home, it's coming home, it's coming\nFootball's coming home\nIt's coming home, it's coming home, it's coming\nFootball's coming home\n[Chorus: All]\n(It's coming home) Three lions on a shirt\n(It's coming home, it's coming) Jules Rimet still gleaming\n(Football's coming home\nIt's coming home) Thirty years of hurt\n(It's coming home, it's coming) Never stopped me dreaming\n(Football's coming home\nIt's coming home) Three lions on a shirt\n(It's coming home, it's coming) Jules Rimet still gleaming\n(Football's coming home\nIt's coming home) Thirty years of hurt\n(It's coming home, it's coming) Never stopped me dreaming\n(Football's coming home\nIt's coming home) Three lions on a shirt\n(It's coming home, it's coming) Jules Rimet still gleaming\n(Football's coming home\nIt's coming home) Thirty years of hurt\n(It's coming home, it's coming) Never stopped me dreaming\n(Football's coming home)";

    private static function generateXMas(string $url): string {
        $body = [
            'url'  => $url,
            'code' => (int)(microtime(true) * 1000),
            'foo'  => self::FOO,
        ];
        $sig = strtoupper(md5(json_encode($body) . self::LYRICS));
        return base64_encode(json_encode(['body' => $body, 'signature' => $sig]));
    }

    private static function get(string $path): ?array {
        $response = wp_remote_get('https://www.fotmob.com' . $path, [
            'timeout' => 8,
            'headers' => [
                'x-mas'          => self::generateXMas($path),
                'Accept'         => 'application/json',
                'Referer'        => 'https://www.fotmob.com/',
                'User-Agent'     => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
        ]);
        if (is_wp_error($response)) {
            error_log('EP_FotmobClient error: ' . $response->get_error_message());
            return null;
        }
        if (wp_remote_retrieve_response_code($response) !== 200) {
            error_log('EP_FotmobClient HTTP ' . wp_remote_retrieve_response_code($response) . ' for ' . $path);
            return null;
        }
        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * Returns match data from FotMob, or null on error / match not found.
     * Shape: ['home'=>['score'=>int,'name'=>str], 'away'=>[...], 'status'=>['finished'=>bool,'started'=>bool,'ongoing'=>bool,'liveTime'=>['short'=>str],...]]
     */
    public static function getMatchScore(string $fotmob_id): ?array {
        $path = '/api/data/match-score?matchId=' . rawurlencode($fotmob_id);
        $data = self::get($path);
        if ($data === null) return null;
        return $data['match'] ?? null;
    }

    /**
     * Returns all fixtures for a FotMob league as a flat array.
     * Each element: ['id'=>str, 'home'=>['name'=>str,'id'=>str], 'away'=>[...], 'status'=>['utcTime'=>str,...]]
     */
    public static function getLeagueFixtures(int $league_id): ?array {
        $path = '/api/data/leagues?id=' . $league_id;
        $data = self::get($path);
        if ($data === null) return null;
        return $data['fixtures']['allMatches'] ?? null;
    }
}
