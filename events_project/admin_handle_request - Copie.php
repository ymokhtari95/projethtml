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

$request_id = $_POST['request_id'] ?? null;
$action     = $_POST['action'] ?? '';

if (!ctype_digit((string)$request_id)) {
    die("ID de demande invalide.");
}
$request_id = (int)$request_id;

if (!in_array($action, ['approve','reject'], true)) {
    die("Action invalide.");
}

// Récupérer la demande
$stmt = $pdo->prepare("SELECT * FROM organizer_requests WHERE id = ?");
$stmt->execute([$request_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    die("Demande introuvable.");
}

$user_id = (int)$request['user_id'];

if ($action === 'approve') {
    // Approuver la demande
    $pdo->beginTransaction();

    // 1) Mettre la demande en 'approved'
    $stmt = $pdo->prepare("UPDATE organizer_requests SET status = 'approved' WHERE id = ?");
    $stmt->execute([$request_id]);

    // 2) Mettre l'utilisateur en 'organizer'
    $stmt = $pdo->prepare("UPDATE users SET role = 'organizer' WHERE id = ?");
    $stmt->execute([$user_id]);

    $pdo->commit();

} elseif ($action === 'reject') {

    $stmt = $pdo->prepare("UPDATE organizer_requests SET status = 'rejected' WHERE id = ?");
    $stmt->execute([$request_id]);
}

header("Location: admin_dashboard.php");
exit;
