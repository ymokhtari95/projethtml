<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_error'] = "Vous devez être connecté pour gérer vos inscriptions.";
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: my_events.php");
    exit;
}

$user_id  = (int)$_SESSION['user_id'];
$event_id = $_POST['event_id'] ?? null;

if (!ctype_digit((string)$event_id)) {
    $_SESSION['flash_error'] = "Événement invalide.";
    header("Location: my_events.php");
    exit;
}
$event_id = (int)$event_id;

// Vérifier que l'inscription existe
$stmt = $pdo->prepare("SELECT id FROM registrations WHERE event_id = ? AND user_id = ?");
$stmt->execute([$event_id, $user_id]);
$registration = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$registration) {
    $_SESSION['flash_error'] = "Vous n'êtes pas inscrit à cet événement.";
    header("Location: my_events.php");
    exit;
}

// Suppression de l'inscription
$stmt = $pdo->prepare("DELETE FROM registrations WHERE id = ?");
$stmt->execute([$registration['id']]);

$_SESSION['flash_success'] = "Vous avez été désinscrit de l'événement.";
header("Location: my_events.php");
exit;

