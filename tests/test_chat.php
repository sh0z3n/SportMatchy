<?php
// Script de test pour l'API chat
// À lancer en CLI : php tests/test_chat.php

function getCsrfAndCookie($loginUrl) {
    $ch = curl_init($loginUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $response = curl_exec($ch);
    preg_match('/Set-Cookie: (PHPSESSID=[^;]+)/', $response, $matches);
    $cookie = $matches[1] ?? '';
    preg_match('/name="csrf_token" value="([^"]+)"/', $response, $matches2);
    $csrf = $matches2[1] ?? '';
    curl_close($ch);
    return [$csrf, $cookie];
}

function apiRequest($url, $data = null, $cookie = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    if ($cookie) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    $response = curl_exec($ch);
    $header = curl_getinfo($ch);
    curl_close($ch);
    return [$response, $header];
}

function login($email, $password) {
    list($csrf, $cookie) = getCsrfAndCookie('http://localhost:8000/login.php');
    $ch = curl_init('http://localhost:8000/login.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'email' => $email,
        'password' => $password,
        'csrf_token' => $csrf,
        'login' => '1'
    ]));
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    $response = curl_exec($ch);
    preg_match_all('/Set-Cookie: ([^;=]+)=([^;]+)/i', $response, $matches, PREG_SET_ORDER);
    $cookies = [];
    foreach ($matches as $m) {
        $cookies[$m[1]] = $m[2];
    }
    $cookieString = '';
    if (isset($cookies['sportmatchy_session'])) {
        $cookieString = 'sportmatchy_session=' . $cookies['sportmatchy_session'];
    }
    curl_close($ch);
    echo "\n--- LOGIN DEBUG for $email ---\n";
    echo "Cookie final: $cookieString\n";
    echo "--- END LOGIN DEBUG ---\n\n";
    return $cookieString;
}

function register($username, $email, $password) {
    // Récupérer CSRF et cookie
    $ch = curl_init('http://localhost:8000/register.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $response = curl_exec($ch);
    preg_match('/Set-Cookie: (PHPSESSID=[^;]+)/', $response, $matches);
    $cookie = $matches[1] ?? '';
    preg_match('/name="csrf_token" value="([^"]+)"/', $response, $matches2);
    $csrf = $matches2[1] ?? '';
    curl_close($ch);
    // Envoyer le POST d'inscription
    $ch = curl_init('http://localhost:8000/register.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'username' => $username,
        'email' => $email,
        'password' => $password,
        'confirm_password' => $password,
        'csrf_token' => $csrf,
        'register' => '1'
    ]));
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    curl_close($ch);
    echo "\n--- REGISTER DEBUG for $email ---\n";
    echo "CSRF: $csrf\n";
    echo "Cookie: $cookie\n";
    echo "Header:\n$header\n";
    echo "Body:\n$body\n";
    echo "--- END REGISTER DEBUG ---\n\n";
}

function getUserIdByEmail($email) {
    $pdo = new PDO('mysql:host=localhost;dbname=sportmatchy', 'sportmatchy', 'sportmatchy123');
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    return $stmt->fetchColumn();
}

// Inscription de deux nouveaux utilisateurs
register('chatuser1', 'chatuser1@ex.com', 'testpass1');
register('chatuser2', 'chatuser2@ex.com', 'testpass2');

// Utiliser ces nouveaux comptes pour le test du chat
$user1_email = 'chatuser1@ex.com';
$user1_pass = 'testpass1';
$user2_email = 'chatuser2@ex.com';
$user2_pass = 'testpass2';

$user1_cookie = login($user1_email, $user1_pass);
$user2_cookie = login($user2_email, $user2_pass);

echo "User1 cookie: $user1_cookie\n";
echo "User2 cookie: $user2_cookie\n";

// 2. Création d'un groupe (DM)
$groupName = 'DM Test';
$user2_id = getUserIdByEmail($user2_email);

$createGroupData = [
    'name' => $groupName,
    'members' => [$user2_id]
];
list($resp, $hdr) = apiRequest('http://localhost:8000/api/chat.php?action=create_group', $createGroupData, $user1_cookie);
echo "Réponse API création groupe : $resp\n";
$group = json_decode($resp, true);
$group_id = $group['group_id'] ?? null;
echo "Groupe créé: $group_id\n";

// 3. Envoi de messages (texte, emoji)
$msg1 = ['group_id' => $group_id, 'message' => 'Bonjour 👋', 'type' => 'text'];
$msg2 = ['group_id' => $group_id, 'message' => 'Salut 😁', 'type' => 'text'];
apiRequest('http://localhost:8000/api/chat.php?action=send', $msg1, $user1_cookie);
apiRequest('http://localhost:8000/api/chat.php?action=send', $msg2, $user2_cookie);

echo "Messages envoyés.\n";

// 4. Ajout d'une réaction
// On récupère le dernier message
echo "Récupération des messages...\n";
list($resp, $hdr) = apiRequest('http://localhost:8000/api/chat.php?action=get_messages&group_id=' . $group_id, null, $user1_cookie);
$messages = json_decode($resp, true)['messages'] ?? [];
$last_msg_id = end($messages)['id'] ?? null;

if ($last_msg_id) {
    $pdo = new PDO('mysql:host=localhost;dbname=sportmatchy', 'sportmatchy', 'sportmatchy123');
    $stmt = $pdo->prepare('INSERT INTO chat_message_reactions (message_id, user_id, reaction) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE reaction = VALUES(reaction)');
    $stmt->execute([$last_msg_id, 2, '👍']);
    echo "Réaction ajoutée sur le message $last_msg_id\n";
}

// 5. Affichage des messages et réactions
echo "Messages dans le groupe :\n";
foreach ($messages as $msg) {
    echo "[{$msg['username']}] {$msg['message']} ({$msg['created_at']})\n";
}

if ($last_msg_id) {
    $stmt = $pdo->prepare('SELECT * FROM chat_message_reactions WHERE message_id = ?');
    $stmt->execute([$last_msg_id]);
    $reactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Réactions sur le dernier message :\n";
    foreach ($reactions as $r) {
        echo "- User {$r['user_id']} : {$r['reaction']}\n";
    }
}

echo "Test terminé.\n"; 