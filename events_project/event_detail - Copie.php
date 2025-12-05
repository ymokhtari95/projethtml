<?php
session_start();
require_once 'config.php';

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    die("ID d'événement invalide.");
}
$event_id = (int)$_GET['id'];

$stmt = $pdo->prepare("
    SELECT e.*, u.name AS organizer_name
    FROM events e
    JOIN users u ON e.organizer_id = u.id
    WHERE e.id = ?
");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die("Événement introuvable.");
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = ?");
$stmt->execute([$event_id]);
$inscrits = (int)$stmt->fetchColumn();
$capacity = (int)$event['capacity'];

$user_is_registered = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT id FROM registrations WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$event_id, $_SESSION['user_id']]);
    $user_is_registered = $stmt->fetch() ? true : false;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($event['title']) ?></title>
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<header class="header">
    <div class="header-title">
        <img src="assets/logo/synapz_logo.png" alt="SYNAPZ" class="logo-synapz">
    </div>
    <nav class="header-nav">
        <a href="index.php">Accueil</a>
        <a href="events_list.php">Événements</a>

        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="my_events.php">Mes inscriptions</a>

            <?php if ($_SESSION['user_role'] === 'organizer' || $_SESSION['user_role'] === 'admin'): ?>
                <a href="my_created_events.php">Mes événements créés</a>
                <a href="create_event.php">Créer un événement</a>
            <?php endif; ?>

            <a href="profile.php">Profil</a>

            <?php if ($_SESSION['user_role'] === 'user'): ?>
                <a href="request_organizer.php">Devenir organisateur</a>
            <?php endif; ?>

            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <a href="admin_dashboard.php">Admin</a>
            <?php endif; ?>

            <a href="logout.php">Déconnexion</a>
        <?php else: ?>
            <a href="login.php">Connexion</a>
            <a href="register.php">Inscription</a>
        <?php endif; ?>
    </nav>
</header>

<main class="main">
<section class="card">

    <h2><?= htmlspecialchars($event['title']) ?></h2>

    <!-- 🔔 Messages flash -->
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($_SESSION['flash_error']) ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['flash_success']) ?>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if (!empty($event['image'])): ?>
        <img src="uploads/<?= htmlspecialchars($event['image']) ?>"
             alt="Image de l'événement"
             style="width:100%;max-height:260px;object-fit:cover;border-radius:10px;margin:12px 0;">
    <?php endif; ?>

    <span class="badge badge-blue">
        <?= htmlspecialchars($event['category']) ?>
    </span>

    <?php if ($inscrits >= $capacity): ?>
        <span class="badge badge-red">Complet</span>
    <?php else: ?>
        <span class="badge badge-green"><?= $inscrits ?>/<?= $capacity ?> places</span>
    <?php endif; ?>

    <?php if ((int)$event['min_age'] > 0): ?>
        <span class="badge badge-blue" style="margin-left:8px;">
            <?= (int)$event['min_age'] ?>+
        </span>
    <?php endif; ?>

    <div class="meta" style="margin-top:15px;">
        Ville : <?= htmlspecialchars($event['city']) ?><br>
        Catégorie : <?= htmlspecialchars($event['category']) ?><br>
        Date : <?= htmlspecialchars($event['date']) ?>
        à <?= htmlspecialchars(substr($event['time'], 0, 5)) ?><br>
        Organisé par : <strong><?= htmlspecialchars($event['organizer_name']) ?></strong><br>
        <?php if ((int)$event['min_age'] > 0): ?>
            Restriction d’âge : <?= (int)$event['min_age'] ?>+<br>
        <?php endif; ?>
        Créé le : <?= htmlspecialchars($event['created_at']) ?>
    </div>

    <hr style="margin:20px 0;border:none;border-top:1px solid #e5e7eb;">

    <h3>Description</h3>
    <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>

    <div style="margin-top:25px;">

        <?php if (!isset($_SESSION['user_id'])): ?>

            <a href="login.php" class="btn">Se connecter pour s'inscrire</a>

        <?php else: ?>

            <?php
            $stmt = $pdo->prepare("SELECT age FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user_age = (int)$stmt->fetchColumn();
            ?>

            <?php if ($user_age < (int)$event['min_age']): ?>

                <p class="alert alert-error">
                    Événement réservé aux <?= (int)$event['min_age'] ?> ans et plus.
                </p>

            <?php elseif ($user_is_registered): ?>

                <form method="post" action="unregister_event.php" class="inline">
                    <input type="hidden" name="event_id" value="<?= $event_id ?>">
                    <button class="btn btn-secondary">Se désinscrire</button>
                </form>

            <?php elseif ($inscrits >= $capacity): ?>

                <p class="alert alert-error">Impossible de s'inscrire : événement complet.</p>

            <?php else: ?>

                <form method="post" action="register_event.php" class="inline">
                    <input type="hidden" name="event_id" value="<?= $event_id ?>">
                    <button class="btn">S'inscrire à cet événement</button>
                </form>

            <?php endif; ?>

        <?php endif; ?>

    </div>

    <?php if (
        isset($_SESSION['user_id']) &&
        ($_SESSION['user_role'] === 'admin' || $_SESSION['user_id'] == $event['organizer_id'])
    ): ?>
        <hr style="margin:25px 0;border:none;border-top:1px solid #e5e7eb;">
        <h3>Gestion de l'événement</h3>

        <a href="edit_event.php?id=<?= $event_id ?>" class="btn btn-secondary">Modifier</a>

        <form action="delete_event.php" method="post" class="inline"
              onsubmit="return confirm('Supprimer cet événement ?');">
            <input type="hidden" name="event_id" value="<?= $event_id ?>">
            <button type="submit" class="btn btn-danger">Supprimer</button>
        </form>
    <?php endif; ?>

</section>
</main>

</body>
</html>




