<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT e.*, u.name AS organizer_name
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    JOIN users u ON e.organizer_id = u.id
    WHERE r.user_id = ?
    ORDER BY e.date ASC, e.time ASC
");
$stmt->execute([$user_id]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

function count_inscriptions($pdo, $event_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id = ?");
    $stmt->execute([$event_id]);
    return (int)$stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes inscriptions</title>
    <link rel="stylesheet" href="style.css?v=4">
</head>
<body>

<header class="header">
    <div class="header-title">
        <img src="assets/logo/synapz_logo.png" alt="SYNAPZ" class="logo-synapz">
    </div>
    <nav class="header-nav">

        <a href="index.php">Accueil</a>
        <a href="events_list.php">Événements</a>
        <a href="my_events.php">Mes inscriptions</a>

        <?php if ($_SESSION['user_role'] !== 'user'): ?>
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
    </nav>
</header>

<main class="main">
<section class="card">

    <h2>Mes événements inscrits</h2>

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

    <?php if (empty($events)): ?>
        <p>Aucun événement pour le moment.</p>
    <?php else: ?>
        <ul class="list">
            <?php foreach ($events as $event): ?>
                <?php
                $inscrits = count_inscriptions($pdo, $event['id']);
                $capacity = (int)$event['capacity'];
                ?>
                <li class="list-item">

                    <?php if (!empty($event['image'])): ?>
                        <img src="uploads/<?= htmlspecialchars($event['image']) ?>"
                             alt="Image de l'événement"
                             style="width:100%;max-height:180px;object-fit:cover;border-radius:8px;margin-bottom:8px;">
                    <?php endif; ?>

                    <strong><?= htmlspecialchars($event['title']) ?></strong>

                    <span class="badge badge-blue">
                        <?= htmlspecialchars($event['category']) ?>
                    </span>

                    <?php if ($inscrits >= $capacity): ?>
                        <span class="badge badge-red">Complet</span>
                    <?php else: ?>
                        <span class="badge badge-green"><?= $inscrits ?>/<?= $capacity ?> places</span>
                    <?php endif; ?>

                    <?php if ((int)$event['min_age'] > 0): ?>
                        <span class="badge badge-blue"><?= (int)$event['min_age'] ?>+</span>
                    <?php endif; ?>

                    <div class="meta">
                        Ville : <?= htmlspecialchars($event['city']) ?><br>
                        Date : <?= htmlspecialchars($event['date']) ?> à <?= htmlspecialchars(substr($event['time'], 0, 5)) ?><br>
                        Organisé par : <?= htmlspecialchars($event['organizer_name']) ?>
                    </div>

                    <!-- 🔘 Boutons alignés comme ailleurs -->
                    <div class="event-actions" style="margin-top:10px;">
                        <a class="btn" href="event_detail.php?id=<?= (int)$event['id'] ?>">Voir</a>

                        <form action="unregister_event.php" method="post" class="inline">
                            <input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>">
                            <button class="btn btn-secondary">Se désinscrire</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

</section>
</main>

</body>
</html>








