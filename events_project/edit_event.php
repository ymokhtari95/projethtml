<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    die("Accès refusé.");
}

$CATEGORIES = ['Soirée', 'Sport', 'Conférence', 'Meetup', 'Concert', 'Gaming', 'Atelier', 'Autre'];

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    die("ID invalide.");
}
$event_id = (int)$_GET['id'];

// Récupérer l'événement
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    die("Événement introuvable.");
}

// Vérifier droits (organisateur ou admin)
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_id'] != $event['organizer_id']) {
    die("Vous n'avez pas le droit de modifier cet événement.");
}

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $city        = trim($_POST['city'] ?? '');
    $category    = trim($_POST['category'] ?? 'Autre');
    $date        = trim($_POST['date'] ?? '');
    $time        = trim($_POST['time'] ?? '');
    $capacity    = trim($_POST['capacity'] ?? '');
    $min_age     = trim($_POST['min_age'] ?? '0');
    if ($min_age === '') $min_age = 0;

    if ($title === '')        $errors[] = "Le titre est obligatoire.";
    if ($description === '')  $errors[] = "La description est obligatoire.";
    if ($city === '')         $errors[] = "La ville est obligatoire.";
    if ($date === '')         $errors[] = "La date est obligatoire.";
    if ($time === '')         $errors[] = "L'heure est obligatoire.";

    if (!in_array($category, $CATEGORIES, true)) {
        $errors[] = "Catégorie invalide.";
    }

    if (!ctype_digit($capacity) || (int)$capacity <= 0) {
        $errors[] = "Capacité invalide.";
    }

    if (!ctype_digit($min_age) || (int)$min_age < 0) {
        $errors[] = "Âge minimum invalide.";
    }

    // Gérer l'image (optionnelle)
    $imageName = $event['image']; // garder l'ancienne par défaut

    if (isset($_FILES['image']) && $_FILES['image']['error'] !== 4) {

        if ($_FILES['image']['error'] !== 0) {
            $errors[] = "Erreur avec l'image.";
        } else {
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            $maxSize = 3 * 1024 * 1024;

            if (!in_array($_FILES['image']['type'], $allowed)) {
                $errors[] = "L'image doit être JPEG, PNG ou WEBP.";
            }

            if ($_FILES['image']['size'] > $maxSize) {
                $errors[] = "Image trop grande (max 3 Mo).";
            }

            if (empty($errors)) {
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $imageName = uniqid("event_") . "." . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $imageName);
            }
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE events
            SET title = :title,
                description = :description,
                city = :city,
                category = :category,
                date = :date,
                time = :time,
                capacity = :capacity,
                min_age = :min_age,
                image = :image
            WHERE id = :id
        ");

        $stmt->execute([
            ':title'       => $title,
            ':description' => $description,
            ':city'        => $city,
            ':category'    => $category,
            ':date'        => $date,
            ':time'        => $time,
            ':capacity'    => $capacity,
            ':min_age'     => $min_age,
            ':image'       => $imageName,
            ':id'          => $event_id
        ]);

        $success = "Événement mis à jour.";
        // mettre à jour $event pour réafficher les bonnes valeurs
        $event['title']       = $title;
        $event['description'] = $description;
        $event['city']        = $city;
        $event['category']    = $category;
        $event['date']        = $date;
        $event['time']        = $time;
        $event['capacity']    = $capacity;
        $event['min_age']     = $min_age;
        $event['image']       = $imageName;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier l'événement</title>
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
        <a href="my_created_events.php">Mes événements créés</a>
        <a href="logout.php">Déconnexion</a>
    </nav>
</header>

<main class="main">
<section class="card">

<h2>Modifier l'événement</h2>

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

<form method="post" enctype="multipart/form-data">

    <div class="form-group">
        <label>Titre :</label>
        <input type="text" name="title" required value="<?= htmlspecialchars($event['title']) ?>">
    </div>

    <div class="form-group">
        <label>Description :</label>
        <textarea name="description" rows="4" required><?= htmlspecialchars($event['description']) ?></textarea>
    </div>

    <div class="form-group">
        <label>Ville :</label>
        <input type="text" name="city" required value="<?= htmlspecialchars($event['city']) ?>">
    </div>

    <div class="form-group">
        <label>Catégorie :</label>
        <select name="category" required>
            <?php foreach ($CATEGORIES as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>"
                    <?= ($event['category'] === $cat) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Date :</label>
        <input type="date" name="date" required value="<?= htmlspecialchars($event['date']) ?>">
    </div>

    <div class="form-group">
        <label>Heure :</label>
        <input type="time" name="time" required value="<?= htmlspecialchars(substr($event['time'],0,5)) ?>">
    </div>

    <div class="form-group">
        <label>Capacité :</label>
        <input type="number" name="capacity" required value="<?= htmlspecialchars($event['capacity']) ?>">
    </div>

    <div class="form-group">
        <label>Âge minimum :</label>
        <input type="number" name="min_age" min="0" max="120"
               value="<?= htmlspecialchars($event['min_age']) ?>">
    </div>

    <div class="form-group">
        <label>Image actuelle :</label><br>
        <?php if (!empty($event['image'])): ?>
            <img src="uploads/<?= htmlspecialchars($event['image']) ?>"
                 alt="Image actuelle"
                 style="max-width:200px;max-height:150px;object-fit:cover;border-radius:8px;">
        <?php else: ?>
            <em>Aucune image.</em>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label>Nouvelle image (optionnel) :</label>
        <input type="file" name="image" accept="image/*">
    </div>

    <button type="submit" class="btn">Enregistrer les modifications</button>

</form>

</section>
</main>

</body>
</html>


