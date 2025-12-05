<?php
session_start();
require_once 'config.php';

// Vérifier admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'admin')) {
    die("Accès refusé.");
}

$user_id = $_POST['user_id'] ?? null;
$role    = $_POST['role'] ?? 'user';

if (!$user_id || !ctype_digit($user_id)) {
    die("ID utilisateur invalide.");
}

$validRoles = ['user', 'organizer', 'admin'];
if (!in_array($role, $validRoles, true)) {
    die("Rôle invalide.");
}

$user_id = (int)$user_id;

// Optionnel : empêcher l'admin de se retirer lui-même son rôle
if ($user_id === (int)$_SESSION['user_id'] && $role !== 'admin') {
    die("Tu ne peux pas enlever ton propre rôle admin ici.");
}

$stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
$stmt->execute([$role, $user_id]);

header('Location: admin_dashboard.php');
exit;
