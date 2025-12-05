<?php
session_start();
require_once 'config.php';

// Petite fonction utilitaire pour les messages flash
function flash_error_and_redirect_event($event_id, $message) {
    $_SESSION['flash_error'] = $message;
    header("Location: event_detail.php?id=" . (int)$event_id);
    exit;
}

function flash_success_and_redirect_my_events($message) {
    $_SESSION['flash_success'] = $message;
    header("Location: my_events.php");
    exit;
}

// Vérifier la connexion
if (!isset($_SESSION['user_id'])) {
    $_SESSION['flash_error'] = "Vous devez être connecté pour vous inscrire à un événement.";
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: events_list.php");
    exit;
}

$user_id  = (int)$_SESSION['user_id'];
$event_id = $_POST['event_id'] ?? null;

if (!ctype_digit((string)$event_id)) {
    $_SESSION['flash_error'] = "Événement invalide.";
    header("Location: events_list.php");
    exit;
}
$event_id = (int)$event_id;

// Récupérer l'événement demandé
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    $_SESSION['flash_error'] = "Événement introuvable.";
    header("Location: events_list.php");
    exit;
}

// Vérifier que l'événement n'est pas dans le passé
$eventDateTime = strtotime($event['date'] . " " . $event['time']);
if ($eventDateTime < time()) {
    flash_error_and_redirect_event($event_id, "Impossible de s'inscrire à un événement déjà passé.");
}

// Vérifier la capacité
$stmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = ?");
$stmt->execute([$event_id]);
$inscrits = (int)$stmt->fetchColumn();

if ($inscrits >= (int)$event['capacity']) {
    flash_error_and_redirect_event($event_id, "L'événement est déjà complet.");
}

// Vérifier restriction d'âge
$stmt = $pdo->prepare("SELECT age FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_age = (int)$stmt->fetchColumn();

if ($user_age < (int)$event['min_age']) {
    flash_error_and_redirect_event($event_id, "Vous n'avez pas l'âge requis pour cet événement.");
}

// 🔥 Empêcher chevauchement horaire le même jour

$target_date = $event['date'];
$target_time = $event['time'];  // "HH:MM:SS"
$target_ts   = strtotime($target_date . " " . $target_time);

// On récupère les événements le même jour où l'utilisateur est déjà inscrit
$stmt = $pdo->prepare("
    SELECT e.*
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    WHERE r.user_id = ?
      AND e.date = ?
");
$stmt->execute([$user_id, $target_date]);
$users_events_same_day = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users_events_same_day as $ev) {
    $ev_ts = strtotime($ev['date'] . " " . $ev['time']);

    // Ici on considère l'heure comme un créneau exact
    if ($ev_ts === $target_ts) {
        flash_error_and_redirect_event(
            $event_id,
            "Vous êtes déjà inscrit à un autre événement à la même heure ce jour-là."
        );
    }
}

// Vérifier si déjà inscrit à cet événement
$stmt = $pdo->prepare("SELECT id FROM registrations WHERE event_id = ? AND user_id = ?");
$stmt->execute([$event_id, $user_id]);

if ($stmt->fetch()) {
    flash_error_and_redirect_event($event_id, "Vous êtes déjà inscrit à cet événement.");
}

// Inscription
$stmt = $pdo->prepare("INSERT INTO registrations (event_id, user_id) VALUES (?, ?)");
$stmt->execute([$event_id, $user_id]);

flash_success_and_redirect_my_events("Inscription à l'événement réalisée avec succès !");



