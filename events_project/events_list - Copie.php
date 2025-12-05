<?php
session_start();
require_once 'config.php';

// Liste des catégories possibles
$CATEGORIES = ['Toutes', 'Soirée', 'Sport', 'Conférence', 'Meetup', 'Concert', 'Gaming', 'Atelier', 'Autre'];

$search       = trim($_GET['search'] ?? '');
$filter_city  = trim($_GET['city'] ?? '');
$filter_cat   = trim($_GET['category'] ?? 'Toutes');
$filter_age   = trim($_GET['min_age'] ?? '');

$sql = "
    SELECT e.*, u.name AS organizer_name
    FROM events e
    JOIN users u ON e.organizer_id = u.id
";
$conditions = [];
$params     = [];

if ($search !== '') {
    $conditions[]      = "(e.title LIKE :search OR e.city LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($filter_city !== '') {
    $conditions[]    = "e.city LIKE :city";
    $params[':city'] = '%' . $filter_city . '%';
}

if ($filter_cat !== '' && $filter_cat !== 'Toutes') {
    $conditions[]        = "e.category = :category";
    $params[':category'] = $filter_cat;
}

if ($filter_age !== '' && ctype_digit($filter_age)) {
    $conditions[]         = "e.min_age >= :min_age";
    $params[':min_age']   = (int)$filter_age;
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY e.date ASC, e.time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
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
    <title>Tous les événements</title>
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
        <?php else: ?>
            <a href="login.php">Connexion</a>
            <a href="register.php">Inscription</a>
        <?php endif; ?>
    </nav>
</header>

<main class="main">
<section class="card">

    <h2>Tous les événements</h2>

    <form method="get" class="filter-form" style="margin-bottom: 20px;">
        <div class="form-group">
            <label for="search">Recherche (titre ou ville) :</label>
            <input type="text" id="search" name="search"
                   value="<?= htmlspecialchars($search) ?>"
                   placeholder="Ex: soirée, foot, Paris...">
        </div>

        <div class="form-group">
            <label for="city">Ville :</label>
            <input type="text" id="city" name="city"
                   value="<?= htmlspecialchars($filter_city) ?>"
                   placeholder="Ex: Paris">
        </div>

        <div class="form-group">
            <label for="category">Catégorie :</label>
            <select id="category" name="category">
                <?php foreach ($CATEGORIES as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>"
                        <?= ($filter_cat === $cat) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="min_age">Âge minimum :</label>
            <select id="min_age" name="min_age">
                <option value="" <?= ($filter_age === '') ? 'selected' : '' ?>>Tous</option>
                <option value="12" <?= ($filter_age === '12') ? 'selected' : '' ?>>12+</option>
                <option value="16" <?= ($filter_age === '16') ? 'selected' : '' ?>>16+</option>
                <option value="18" <?= ($filter_age === '18') ? 'selected' : '' ?>>18+</option>
            </select>
        </div>

        <button type="submit" class="btn">Filtrer</button>
        <a href="events_list.php" class="btn btn-secondary" style="margin-left: 8px;">Réinitialiser</a>
    </form>

    <?php if (empty($events)): ?>
        <p>Aucun événement ne correspond à ces critères.</p>
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
                        Le <?= htmlspecialchars($event['date']) ?>
                        à <?= htmlspecialchars(substr($event['time'], 0, 5)) ?><br>
                        Organisé par : <?= htmlspecialchars($event['organizer_name']) ?>
                    </div>

                    <div style="margin-top:10px;">
                        <a class="btn" href="event_detail.php?id=<?= (int)$event['id'] ?>">Voir</a>
                    </div>

                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

</section>
</main>

</body>
</html>




