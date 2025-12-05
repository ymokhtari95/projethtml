<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$errors = [];
$success = "";

// Récupérer les infos actuelles de l'utilisateur
$stmt = $pdo->prepare("SELECT id, name, email, age, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Utilisateur introuvable.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $age  = trim($_POST['age'] ?? '');

    if ($name === '') {
        $errors[] = "Le nom est obligatoire.";
    }

    if (!ctype_digit($age) || (int)$age < 1 || (int)$age > 120) {
        $errors[] = "L'âge doit être un nombre entre 1 et 120.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET name = :name, age = :age WHERE id = :id");
        $stmt->execute([
            ':name' => $name,
            ':age'  => $age,
            ':id'   => $user_id
        ]);

        $success      = "Profil mis à jour avec succès.";
        $user['name'] = $name;
        $user['age']  = $age;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon profil</title>
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

    <h2>Mon profil</h2>

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

    <form method="post">

        <div class="form-group">
            <label>Nom :</label>
            <input type="text" name="name" required
                   value="<?= htmlspecialchars($user['name']) ?>">
        </div>

        <div class="form-group">
            <label>Âge :</label>
            <input type="number" name="age" required min="1" max="120"
                   value="<?= htmlspecialchars($user['age']) ?>">
        </div>

        <div class="form-group">
            <label>Email (non modifiable) :</label>
            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
        </div>

        <div class="form-group">
            <label>Rôle :</label>
            <input type="text" value="<?= htmlspecialchars($user['role']) ?>" disabled>
        </div>

        <button type="submit" class="btn">Mettre à jour</button>
    </form>

</section>
</main>

</body>
</html>