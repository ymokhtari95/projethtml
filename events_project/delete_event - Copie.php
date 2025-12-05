<?php
session_start();
require_once 'config.php';

// Vérifier connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id  = (int)$_SESSION['user_id'];
$role     = $_SESSION['user_role'] ?? 'user';
$event_id = $_POST['event_id'] ?? null;

if (!$event_id || !ctype_digit($event_id)) {
    die("ID d'événement invalide.");
}

$event_id = (int)$event_id;

// Vérifier que l'utilisateur est bien l'organisateur ou admin
if ($role === 'admin') {
    $stmt = $pdo->prepare("SELECT id FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
} else {
    $stmt = $pdo->prepare("SELECT id FROM events WHERE id = ? AND organizer_id = ?");
    $stmt->execute([$event_id, $user_id]);
}
$event = $stmt->fetch();

if (!$event) {
    die("Événement introuvable ou tu n'es pas autorisé à le supprimer.");
}

// Supprimer d'abord les inscriptions
$stmt = $pdo->prepare("DELETE FROM registrations WHERE event_id = ?");
$stmt->execute([$event_id]);

// Puis l'événement
$stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
$stmt->execute([$event_id]);

if ($role === 'admin') {
    header('Location: admin_dashboard.php');
} else {
    header('Location: my_created_events.php');
}
exit;
