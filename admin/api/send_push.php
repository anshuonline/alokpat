<?php
/**
 * Send Firebase Push Notification
 */

function generateFirebaseJWT($serviceAccountJson) {
    $serviceAccount = json_decode($serviceAccountJson, true);
    if (!$serviceAccount || !isset($serviceAccount['private_key']) || !isset($serviceAccount['client_email'])) {
        return false;
    }

    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $now = time();
    $payload = json_encode([
        'iss' => $serviceAccount['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600
    ]);

    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

    $signature = '';
    openssl_sign($base64UrlHeader . "." . $base64UrlPayload, $signature, $serviceAccount['private_key'], 'sha256WithRSAEncryption');
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

function getFirebaseAccessToken($serviceAccountJson) {
    $jwt = generateFirebaseJWT($serviceAccountJson);
    if (!$jwt) return false;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['access_token'] ?? false;
}

function sendFirebasePushNotification($post_id) {
    global $db, $setting;
    
    // Check if configuration exists
    $project_id = $setting->get('fcm_project_id');
    $serviceAccountJson = $setting->get('fcm_service_account_json');
    
    if (empty($project_id) || empty($serviceAccountJson)) {
        return ['success' => false, 'error' => 'Firebase is not configured. Please check API settings.'];
    }
    
    // Get post details
    try {
        $stmt = $db->prepare("SELECT title, excerpt, slug, image FROM posts WHERE id = ?");
        $stmt->execute([$post_id]);
        $post = $stmt->fetch();
        if (!$post) return ['success' => false, 'error' => 'Post not found'];
    } catch(PDOException $e) {
        return ['success' => false, 'error' => 'Database error'];
    }
    
    // Get subscribers
    $subscribers = [];
    try {
        $stmt = $db->query("SELECT token FROM fcm_subscribers");
        $subscribers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch(PDOException $e) {}
    
    if (empty($subscribers)) {
        return ['success' => false, 'error' => 'No subscribers found'];
    }
    
    // Get access token
    $accessToken = getFirebaseAccessToken($serviceAccountJson);
    if (!$accessToken) {
        return ['success' => false, 'error' => 'Failed to authenticate with Firebase Service Account. Check JSON.'];
    }
    
    $post_url = SITE_URL . '/post/' . $post['slug'];
    $image_url = !empty($post['image']) ? UPLOAD_URL . '/' . $post['image'] : '';
    
    // Send notifications using curl_multi for parallel requests
    $mh = curl_multi_init();
    $curl_handles = [];
    
    $url = "https://fcm.googleapis.com/v1/projects/{$project_id}/messages:send";
    
    foreach ($subscribers as $token) {
        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => mb_strimwidth($post['title'], 0, 100, '...'),
                    'body' => mb_strimwidth($post['excerpt'] ?: $post['title'], 0, 150, '...')
                ],
                'webpush' => [
                    'fcm_options' => [
                        'link' => $post_url
                    ]
                ]
            ]
        ];
        
        if ($image_url) {
            $payload['message']['webpush']['notification'] = [
                'image' => $image_url
            ];
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Timeout to prevent hanging
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        curl_multi_add_handle($mh, $ch);
        $curl_handles[$token] = $ch;
    }
    
    // Execute all queries simultaneously
    $running = null;
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh);
    } while ($running > 0);
    
    $success_count = 0;
    $failure_count = 0;
    
    // Check responses and clean up invalid tokens
    $tokens_to_delete = [];
    foreach ($curl_handles as $token => $ch) {
        $response = curl_multi_getcontent($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($http_code == 200) {
            $success_count++;
        } else {
            $failure_count++;
            $resData = json_decode($response, true);
            // If token is unregistered, delete it from DB
            if (isset($resData['error']['details'])) {
                foreach ($resData['error']['details'] as $detail) {
                    if (isset($detail['errorCode']) && $detail['errorCode'] === 'UNREGISTERED') {
                        $tokens_to_delete[] = $token;
                    }
                }
            }
        }
        
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
    
    // Delete invalid tokens
    if (!empty($tokens_to_delete)) {
        try {
            $inQuery = implode(',', array_fill(0, count($tokens_to_delete), '?'));
            $stmt = $db->prepare("DELETE FROM fcm_subscribers WHERE token IN ($inQuery)");
            $stmt->execute($tokens_to_delete);
        } catch(PDOException $e) {}
    }
    
    return [
        'success' => true,
        'success_count' => $success_count,
        'failure_count' => $failure_count
    ];
}
