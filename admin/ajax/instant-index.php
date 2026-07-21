<?php
/**
 * Instant Indexing AJAX Handler
 */
require_once '../../config/config.php';
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$post_id = $_POST['post_id'] ?? null;

if (!$post_id) {
    echo json_encode(['success' => false, 'message' => 'Post ID is required.']);
    exit;
}

try {
    $post_model = new Post();
    $post = $post_model->getById($post_id);
    
    if (!$post) {
        throw new Exception("Post not found.");
    }
    
    // Check if published
    if ($post['status'] !== 'published') {
        throw new Exception("Only published posts can be indexed.");
    }

    $url = url_for_post($post);
    
    // Path to the JSON key file
    $keyFilePath = BASE_PATH . '/config/google-indexing-key.json';
    
    if (!file_exists($keyFilePath)) {
        throw new Exception("Google Indexing Key file is missing.");
    }
    
    $keyData = json_decode(file_get_contents($keyFilePath), true);
    if (!$keyData || empty($keyData['private_key']) || empty($keyData['client_email'])) {
        throw new Exception("Invalid Google Indexing Key file format.");
    }
    
    // Base64Url encode helper
    function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    // Generate JWT
    $header = [
        "alg" => "RS256",
        "typ" => "JWT"
    ];
    
    $now = time();
    $payload = [
        "iss" => $keyData['client_email'],
        "sub" => $keyData['client_email'],
        "aud" => "https://oauth2.googleapis.com/token",
        "iat" => $now,
        "exp" => $now + 3600,
        "scope" => "https://www.googleapis.com/auth/indexing"
    ];
    
    $segments = [];
    $segments[] = base64url_encode(json_encode($header));
    $segments[] = base64url_encode(json_encode($payload));
    
    $stringToSign = implode('.', $segments);
    $signature = '';
    
    $success = openssl_sign($stringToSign, $signature, $keyData['private_key'], "SHA256");
    if (!$success) {
        throw new Exception("Failed to sign JWT token. Ensure OpenSSL is configured correctly.");
    }
    
    $segments[] = base64url_encode($signature);
    $jwt = implode('.', $segments);
    
    // Request Access Token from Google
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://oauth2.googleapis.com/token");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $responseData = json_decode($response, true);
    if ($httpCode !== 200 || empty($responseData['access_token'])) {
        throw new Exception("Failed to get access token from Google. " . ($responseData['error_description'] ?? ''));
    }
    
    $accessToken = $responseData['access_token'];
    
    // Send URL to Indexing API
    $endpoint = "https://indexing.googleapis.com/v3/urlNotifications:publish";
    $content = json_encode([
        'url' => $url,
        'type' => 'URL_UPDATED'
    ]);
    
    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, $endpoint);
    curl_setopt($ch2, CURLOPT_POST, true);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, $content);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, true);
    
    $indexResponse = curl_exec($ch2);
    $indexHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);
    
    $indexData = json_decode($indexResponse, true);
    
    if ($indexHttpCode === 200) {
        echo json_encode([
            'success' => true, 
            'message' => 'Successfully submitted to Google for instant indexing!',
            'url' => $url
        ]);
    } else {
        $errorMsg = $indexData['error']['message'] ?? 'Unknown error from Indexing API.';
        throw new Exception("Google API Error: " . $errorMsg);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
