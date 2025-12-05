<?php
// email_verifier.php
require_once 'secrets.php';

/**
 * Vérifie un email via l'API Hunter.io
 *
 * Retourne :
 * [
 *   'success' => bool,   // false = problème API / réseau
 *   'is_ok'   => bool,   // true = email acceptable
 *   'score'   => int|null,
 *   'status'  => string|null, // deliverable, risky, undeliverable, unknown...
 * ]
 */
function verify_email_with_hunter(string $email): array
{
    if (!defined('HUNTER_API_KEY') || !HUNTER_API_KEY) {
        return [
            'success' => false,
            'is_ok'   => false,
            'score'   => null,
            'status'  => null,
        ];
    }

    $url = 'https://api.hunter.io/v2/email-verifier?email='
        . urlencode($email)
        . '&api_key=' . urlencode(HUNTER_API_KEY);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        curl_close($ch);
        return [
            'success' => false,
            'is_ok'   => false,
            'score'   => null,
            'status'  => null,
        ];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return [
            'success' => false,
            'is_ok'   => false,
            'score'   => null,
            'status'  => null,
        ];
    }

    $json = json_decode($response, true);

    if (!$json || !isset($json['data'])) {
        return [
            'success' => false,
            'is_ok'   => false,
            'score'   => null,
            'status'  => null,
        ];
    }

    $data   = $json['data'];
    $score  = $data['score']  ?? null;      // 0–100
    $status = $data['result'] ?? null;      // deliverable / risky / undeliverable / unknown

    // Politique simple :
    // - undeliverable  => on refuse
    // - score < 60     => on refuse
    // - risky/unknown  => on accepte mais tu peux le changer
    $is_ok = true;

    if ($status === 'undeliverable') {
        $is_ok = false;
    } elseif ($score !== null && $score < 60) {
        $is_ok = false;
    }

    return [
        'success' => true,
        'is_ok'   => $is_ok,
        'score'   => $score,
        'status'  => $status,
    ];
}

