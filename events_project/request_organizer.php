<?php
session_start();
require_once 'config.php';

// Doit être connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Si déjà organizer ou admin → pas besoin de demander
if ($_SESSION['user_role'] === 'organizer' || $_SESSION['user_role'] === 'admin') {
    die("Tu es déjà organisateur ou administrateur.");
}

$user_id   = (int)$_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? '';
$user_email = '';

// On récupère l'email depuis la BDD pour l'afficher
$stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    $user_email = $row['email'];
}

// Vérifier s'il existe déjà une demande
$stmt = $pdo->prepare("
    SELECT * FROM organizer_requests
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([$user_id]);
$lastRequest = $stmt->fetch(PDO::FETCH_ASSOC);

$errors = [];
$success = "";

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Si déjà une demande en attente, on bloque
    if ($lastRequest && $lastRequest['status'] === 'pending') {
        $errors[] = "Tu as déjà une demande en attente. Merci d'attendre la réponse de l'administrateur.";
    } else {
        $event_types    = trim($_POST['event_types'] ?? '');
        $experience     = trim($_POST['experience'] ?? '');
        $motivations    = trim($_POST['motivations'] ?? '');
        $additional_info = trim($_POST['additional_info'] ?? '');

        if ($event_types === '') {
            $errors[] = "Merci de préciser le type d'événements que tu veux organiser.";
        }
        if ($experience === '') {
            $errors[] = "Merci de décrire un minimum ton expérience (même si elle est faible).";
        }
        if ($motivations === '') {
            $errors[] = "Merci d'expliquer pourquoi tu veux devenir organisateur.";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("
                INSERT INTO organizer_requests (user_id, event_types, experience, motivations, additional_info)
                VALUES (:user_id, :event_types, :experience, :motivations, :additional_info)
            ");

            $stmt->execute([
                ':user_id'        => $user_id,
                ':event_types'    => $event_types,
                ':experience'     => $experience,
                ':motivations'    => $motivations,
                ':additional_info'=> $additional_info !== '' ? $additional_info : null
            ]);

            $success = "Ta demande a été envoyée à l'administrateur. Elle sera examinée prochainement.";
            // On recharge la dernière demande
            $stmt = $pdo->prepare("
                SELECT * FROM organizer_requests
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$user_id]);
            $lastRequest = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Demander à devenir organisateur</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="header">
    <h1 class="header-title">SYNAPZ</h1>
    <nav class="header-nav">
        <a href="index.php">Accueil</a>
        <a href="events_list.php">Événements</a>
        <a href="my_events.php">Mes inscriptions</a>
        <a href="logout.php">Déconnexion</a>
    </nav>
</header>

<main class="main">

    <section class="card">
        <h2>Demande pour devenir organisateur</h2>

        <p class="meta">
            Ces informations aideront l'administrateur à décider si ton profil est sérieux ou si tu es un potentiel troll 😈.
        </p>

        <div style="margin-bottom: 15px;">
            <strong>Ton profil</strong><br>
            Nom : <?= htmlspecialchars($user_name) ?><br>
            Email : <?= htmlspecialchars($user_email) ?>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($lastRequest): ?>
            <div class="alert">
                <strong>Dernière demande :</strong><br>
                Statut : 
                <?php if ($lastRequest['status'] === 'pending'): ?>
                    <span class="badge badge-blue">En attente</span>
                <?php elseif ($lastRequest['status'] === 'approved'): ?>
                    <span class="badge badge-green">Approuvée</span>
                <?php else: ?>
                    <span class="badge badge-red">Refusée</span>
                <?php endif; ?><br>
                Envoyée le : <?= htmlspecialchars($lastRequest['created_at']) ?>
            </div>
        <?php endif; ?>

        <?php if (!$lastRequest || $lastRequest['status'] !== 'pending'): ?>
            <form method="post" action="">
                <div class="form-group">
                    <label for="event_types">Type d'événements que tu comptes organiser :</label>
                    <input type="text" name="event_types" id="event_types" required
                           placeholder="Ex : soirées étudiantes, tournois e-sport, ateliers, conférences..."
                           value="<?= isset($event_types) ? htmlspecialchars($event_types) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="experience">Ton expérience dans la création / gestion d'événements :</label>
                    <textarea name="experience" id="experience" rows="4" required
                              placeholder="Parle de tes projets, assos, événements que tu as aidé à organiser, ou même de ton envie d'apprendre."><?= isset($experience) ? htmlspecialchars($experience) : '' ?></textarea>
                </div>

                <div class="form-group">
                    <label for="motivations">Pourquoi tu veux devenir organisateur sur cette plateforme ?</label>
                    <textarea name="motivations" id="motivations" rows="4" required
                              placeholder="Tes motivations, ton sérieux, ce que tu veux apporter aux utilisateurs."><?= isset($motivations) ? htmlspecialchars($motivations) : '' ?></textarea>
                </div>

                <div class="form-group">
                    <label for="additional_info">Infos supplémentaires (optionnel) :</label>
                    <textarea name="additional_info" id="additional_info" rows="3"
                              placeholder="Liens vers des projets, réseaux, site perso, ou toute info utile."><?= isset($additional_info) ? htmlspecialchars($additional_info) : '' ?></textarea>
                </div>

                <button type="submit" class="btn">Envoyer ma demande</button>
            </form>
        <?php else: ?>
            <p>
                Tu as déjà une demande <strong>en attente</strong>.  
                Merci d'attendre qu'un administrateur la traite.
            </p>
        <?php endif; ?>
    </section>

</main>

</body>
</html>
