<?php
/**
 * Standalone Debug Script for Google Instant Indexing
 * Usage: https://alokpat.in/debug-index.php?id=POST_ID
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/database/Database.php';
require_once __DIR__ . '/models/Post.php';
require_once __DIR__ . '/helpers/functions.php';

// Only allow super_admin for security (or comment this out if you just want to test without login)
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'super_admin', 'editor', 'writer'])) {
    die("<h3>Error: You must be logged in as an admin or staff to run this debug script.</h3>");
}

$post_id = $_GET['id'] ?? null;
if (!$post_id) {
    die("<h3>Please provide a post ID. Example: ?id=132</h3>");
}

echo "<h2>Google Indexing API Debugger</h2>";
echo "Testing Post ID: " . htmlspecialchars($post_id) . "<br><br>";

try {
    $post_model = new Post();
    $post = $post_model->getById($post_id);
    
    if (!$post) {
        throw new Exception("Post not found.");
    }
    
    echo "Post Title: " . htmlspecialchars($post['title']) . "<br>";
    echo "Post Status: " . htmlspecialchars($post['status']) . "<br>";
    
    $url = url_for_post($post);
    echo "Generated URL for Indexing: <strong><a href='$url' target='_blank'>$url</a></strong><br><br>";
    
    $keyFilePath = BASE_PATH . '/config/google-indexing-key.json';
    if (!file_exists($keyFilePath)) {
        throw new Exception("Google Indexing Key file is missing at: $keyFilePath");
    }
    
    $keyData = json_decode(file_get_contents($keyFilePath), true);
    if (!$keyData || empty($keyData['private_key']) || empty($keyData['client_email'])) {
        throw new Exception("Invalid Google Indexing Key file format.");
    }
    
    echo "Service Account Email: " . $keyData['client_email'] . "<br>";
    
    // JWT Generation
    function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    $header = ["alg" => "RS256", "typ" => "JWT"];
    $now = time();
    $payload = [
        "iss" => $keyData['client_email'],
        "sub" => $keyData['client_email'],
        "aud" => "https://oauth2.googleapis.com/token",
        "iat" => $now,
        "exp" => $now + 3600,
        "scope" => "https://www.googleapis.com/auth/indexing"
    ];
    
    $segments = [
        base64url_encode(json_encode($header)),
        base64url_encode(json_encode($payload))
    ];
    
    $stringToSign = implode('.', $segments);
    $signature = '';
    
    if (!openssl_sign($stringToSign, $signature, $keyData['private_key'], "SHA256")) {
        throw new Exception("Failed to sign JWT token. Ensure OpenSSL is configured correctly.");
    }
    
    $segments[] = base64url_encode($signature);
    $jwt = implode('.', $segments);
    
    echo "<br><strong>Requesting Access Token...</strong><br>";
    
    // Request Token
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
    
    echo "Token HTTP Code: $httpCode<br>";
    echo "Token Response: <pre>" . htmlspecialchars($response) . "</pre><br>";
    
    $responseData = json_decode($response, true);
    if ($httpCode !== 200 || empty($responseData['access_token'])) {
        throw new Exception("Failed to get access token from Google.");
    }
    
    $accessToken = $responseData['access_token'];
    
    echo "<strong>Submitting URL to Indexing API...</strong><br>";
    
    // Send URL
    $endpoint = "https://indexing.googleapis.com/v3/urlNotifications:publish";
    $content = json_encode([
        'url' => $url,
        'type' => 'URL_UPDATED'
    ]);
    
    echo "Payload: <pre>$content</pre><br>";
    
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
    
    echo "Indexing API HTTP Code: <strong>$indexHttpCode</strong><br>";
    echo "Indexing API Response: <pre>" . htmlspecialchars($indexResponse) . "</pre><br>";
    
    if ($indexHttpCode === 200) {
        echo "<h3 style='color:green;'>SUCCESS: The API request was accepted by Google!</h3>";
        echo "<p>Note: If it still does not index instantly, it means Google is ignoring the request on their end (Google officially limits this API to JobPosting and BroadcastEvent structured data, and may silently queue normal articles instead of indexing them instantly).</p>";
    } else {
        echo "<h3 style='color:red;'>FAILED: Google API rejected the request.</h3>";
    }
    
} catch (Exception $e) {
    echo "<h3 style='color:red;'>Error Caught:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
