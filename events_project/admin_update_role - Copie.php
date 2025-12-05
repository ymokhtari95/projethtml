<?php
session_start();
require_once 'config.php';

// Vérifier l'admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die("Accès refusé.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_dashboard.php");
    exit;
}

$user_id = $_POST['user_id'] ?? null;
$new_role = $_POST['role'] ?? '';

if (!ctype_digit((string)$user_id)) {
    die("ID utilisateur invalide.");
}
$user_id = (int)$user_id;

// Rôles autorisés
$allowed_roles = ['user', 'organizer', 'admin'];
if (!in_array($new_role, $allowed_roles, true)) {
    die("Rôle invalide.");
}

// Protection de base : empêcher de se retirer soi-même le rôle admin (optionnel)
if ($user_id === (int)$_SESSION['user_id'] && $new_role !== 'admin') {
    // On pourrait autoriser, mais en général on évite qu'un admin se supprime lui-même
    // Ici, on bloque
    die("Vous ne pouvez pas retirer vos privilèges admin vous-même.");
}

// Mise à jour
$stmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
$stmt->execute([
    ':role' => $new_role,
    ':id'   => $user_id
]);

header("Location: admin_dashboard.php");
exit;
