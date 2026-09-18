<?php
$host = strtolower($_SERVER['HTTP_HOST'] ?? '');

// Redirection EXCLUSIVE au sous-domaine de la pharmacie
if (strpos($host, 'souley-guirou') !== false) {
    header('Location: pharmacie-gestion/', true, 302);
    exit();
}

// Si la requête provient du domaine principal danayaplus.com :
// Ne JAMAIS rediriger vers la pharmacie !
if (file_exists(__DIR__ . '/home.html')) {
    include __DIR__ . '/home.html';
    exit();
}
if (file_exists(__DIR__ . '/index.html')) {
    include __DIR__ . '/index.html';
    exit();
}
?>
