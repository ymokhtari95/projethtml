<?php
session_start();
require_once 'config.php';

// Vérifier admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die("Accès refusé.");
}

$request_id = $_POST['request_id'] ?? null;
$action     = $_POST['action'] ?? null;

if (!$request_id || !ctype_digit($request_id)) {
    die("ID de demande invalide.");
}
$request_id = (int)$request_id;

if (!in_array($action, ['approve', 'reject'], true)) {
    die("Action invalide.");
}

// Récupérer la demande
$stmt = $pdo->prepare("
    SELECT r.*, u.id AS user_id, u.role AS user_role
    FROM organizer_requests r
    JOIN users u ON r.user_id = u.id
    WHERE r.id = ?
");
$stmt->execute([$request_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    die("Demande introuvable.");
}

// Si déjà traitée, on ne refait rien
if ($request['status'] !== 'pending') {
    header('Location: admin_dashboard.php');
    exit;
}

if ($action === 'approve') {
    // 1. passer l'utilisateur en organizer (s'il n'est pas admin)
    if ($request['user_role'] !== 'admin') {
        $stmt = $pdo->prepare("UPDATE users SET role = 'organizer' WHERE id = ?");
        $stmt->execute([$request['user_id']]);
    }

    // 2. mettre à jour la demande
    $stmt = $pdo->prepare("
        UPDATE organizer_requests
        SET status = 'approved', reviewed_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$request_id]);

} elseif ($action === 'reject') {

    $stmt = $pdo->prepare("
        UPDATE organizer_requests
        SET status = 'rejected', reviewed_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$request_id]);
}

header('Location: admin_dashboard.php');
exit;
