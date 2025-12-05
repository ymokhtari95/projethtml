<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['organizer', 'admin'])) {
    die("Accès refusé.");
}

$user_id = (int)$_SESSION['user_id'];

if ($_SESSION['user_role'] === 'admin') {
    $stmt = $pdo->query("
        SELECT e.*, u.name AS organizer_name
        FROM events e
        JOIN users u ON e.organizer_id = u.id
        ORDER BY e.date ASC, e.time ASC
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT e.*, u.name AS organizer_name
        FROM events e
        JOIN users u ON e.organizer_id = u.id
        WHERE organizer_id = ?
        ORDER BY e.date ASC, e.time ASC
    ");
    $stmt->execute([$user_id]);
}

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
<title>Mes événements créés</title>
<link rel="stylesheet" href="style.css?v=4">
</head>
<body>

<header class="header">
    <div class="header-title">
        <img src="assets/logo/synapz_logo.png" class="logo-synapz" alt="SYNAPZ">
    </div>

    <nav class="header-nav">
        <a href="index.php">Accueil</a>
        <a href="events_list.php">Événements</a>
        <a href="my_events.php">Mes inscriptions</a>
        <a href="my_created_events.php">Mes événements créés</a>

        <?php if ($_SESSION['user_role'] != "user"): ?>
            <a href="create_event.php">Créer un événement</a>
        <?php endif; ?>

        <a href="profile.php">Profil</a>

        <?php if ($_SESSION['user_role'] === "admin"): ?>
            <a href="admin_dashboard.php">Admin</a>
        <?php endif; ?>

        <a href="logout.php" style="background:linear-gradient(to right,#f97316,#fb7185);color:#0f172a;font-weight:700;">Déconnexion</a>
    </nav>
</header>


<main class="main">

<section class="card">
<h2>Mes événements créés</h2>

<?php if (empty($events)): ?>
    <p style="margin-top:8px;">Aucun événement créé.</p>

<?php else: ?>
<ul class="list">

<?php foreach ($events as $event): ?>
<?php
$inscrits = count_inscriptions($pdo, $event['id']);
$capacity = (int)$event['capacity'];
?>
<li class="list-item">

    <!-- IMAGE -->
    <?php if (!empty($event['image'])): ?>
    <img src="uploads/<?= htmlspecialchars($event['image']) ?>"
        style="width:100%;max-height:200px;object-fit:cover;border-radius:8px;margin-bottom:8px;">
    <?php endif; ?>

    <strong style="font-size:1.2rem;"><?= htmlspecialchars($event['title']) ?></strong>

    <span class="badge badge-blue"><?= htmlspecialchars($event['category']) ?></span>

    <?php if ($inscrits >= $capacity): ?>
        <span class="badge badge-red">Complet</span>
    <?php else: ?>
        <span class="badge badge-green"><?= $inscrits ?>/<?= $capacity ?> places</span>
    <?php endif; ?>

    <?php if ((int)$event['min_age'] > 0): ?>
        <span class="badge badge-blue"><?= (int)$event['min_age'] ?>+</span>
    <?php endif; ?>

    <!-- INFOS -->
    <div class="meta">
        📍 <?= htmlspecialchars($event['city']) ?><br>
        📅 <?= htmlspecialchars($event['date']) ?> — <?= substr($event['time'],0,5) ?><br>
        👤 Organisé par : <?= htmlspecialchars($event['organizer_name']) ?>
    </div>

    <!-- 🔥 BOUTONS FIX -->
    <div class="event-actions">

        <a class="btn" href="event_detail.php?id=<?= (int)$event['id'] ?>">Voir</a>

        <a class="btn btn-secondary" href="edit_event.php?id=<?= (int)$event['id'] ?>">Modifier</a>

        <form action="delete_event.php" method="post"
              onsubmit="return confirm('Supprimer cet événement ?');">
            <input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>">
            <button class="btn btn-danger">Supprimer</button>
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



