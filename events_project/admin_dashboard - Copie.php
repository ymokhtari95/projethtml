<?php
session_start();
require_once 'config.php';

// Vérifier que l'utilisateur est admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    die("Accès réservé à l'administrateur.");
}

// --- STATS GLOBALES ---

// Nombre d'utilisateurs
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$total_users = (int)$stmt->fetchColumn();

// Nombre d'événements
$stmt = $pdo->query("SELECT COUNT(*) FROM events");
$total_events = (int)$stmt->fetchColumn();

// Nombre total d'inscriptions
$stmt = $pdo->query("SELECT COUNT(*) FROM registrations");
$total_registrations = (int)$stmt->fetchColumn();

// Nombre de demandes organisateur en attente
$stmt = $pdo->query("SELECT COUNT(*) FROM organizer_requests WHERE status = 'pending'");
$pending_requests = (int)$stmt->fetchColumn();

// --- LISTE UTILISATEURS (10 derniers) ---
$stmt = $pdo->query("
    SELECT id, name, email, role, age
    FROM users
    ORDER BY id DESC
    LIMIT 10
");
$last_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- LISTE DEMANDES ORGANISATEUR EN ATTENTE ---
$stmt = $pdo->query("
    SELECT r.*, u.name AS user_name, u.email AS user_email, u.age AS user_age
    FROM organizer_requests r
    JOIN users u ON r.user_id = u.id
    WHERE r.status = 'pending'
    ORDER BY r.id ASC
");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- DERNIERS ÉVÉNEMENTS (10 derniers) ---
$stmt = $pdo->query("
    SELECT e.*, u.name AS organizer_name
    FROM events e
    JOIN users u ON e.organizer_id = u.id
    ORDER BY e.date DESC, e.time DESC
    LIMIT 10
");
$last_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour compter les inscrits à un événement
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
    <title>Dashboard Admin</title>
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
        <a href="my_created_events.php">Mes événements créés</a>
        <a href="create_event.php">Créer un événement</a>
        <a href="profile.php">Profil</a>
        <a href="admin_dashboard.php">Admin</a>
        <a href="logout.php">Déconnexion</a>
    </nav>
</header>

<main class="main">
<section class="card">

    <h2>Dashboard administrateur</h2>

    <!-- STATS -->
    <div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:15px 0;">
        <div class="card" style="padding:10px;">
            <h3>Total utilisateurs</h3>
            <p style="font-size:1.6em;font-weight:bold;"><?= $total_users ?></p>
        </div>
        <div class="card" style="padding:10px;">
            <h3>Total événements</h3>
            <p style="font-size:1.6em;font-weight:bold;"><?= $total_events ?></p>
        </div>
        <div class="card" style="padding:10px;">
            <h3>Total inscriptions</h3>
            <p style="font-size:1.6em;font-weight:bold;"><?= $total_registrations ?></p>
        </div>
        <div class="card" style="padding:10px;">
            <h3>Demandes orga en attente</h3>
            <p style="font-size:1.6em;font-weight:bold;"><?= $pending_requests ?></p>
        </div>
    </div>

    <hr>

    <!-- UTILISATEURS -->
    <h3>Derniers utilisateurs inscrits</h3>

    <?php if (empty($last_users)): ?>
        <p>Aucun utilisateur trouvé.</p>
    <?php else: ?>
        <table class="table" style="width:100%;border-collapse:collapse;margin-bottom:20px;">
            <thead>
                <tr>
                    <th style="border-bottom:1px solid #ddd;padding:6px;">ID</th>
                    <th style="border-bottom:1px solid #ddd;padding:6px;">Nom</th>
                    <th style="border-bottom:1px solid #ddd;padding:6px;">Email</th>
                    <th style="border-bottom:1px solid #ddd;padding:6px;">Âge</th>
                    <th style="border-bottom:1px solid #ddd;padding:6px;">Rôle</th>
                    <th style="border-bottom:1px solid #ddd;padding:6px;">Changer de rôle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($last_users as $u): ?>
                    <tr>
                        <td style="border-bottom:1px solid #eee;padding:6px;"><?= (int)$u['id'] ?></td>
                        <td style="border-bottom:1px solid #eee;padding:6px;"><?= htmlspecialchars($u['name']) ?></td>
                        <td style="border-bottom:1px solid #eee;padding:6px;"><?= htmlspecialchars($u['email']) ?></td>
                        <td style="border-bottom:1px solid #eee;padding:6px;"><?= htmlspecialchars($u['age']) ?></td>
                        <td style="border-bottom:1px solid #eee;padding:6px;"><?= htmlspecialchars($u['role']) ?></td>
                        <td style="border-bottom:1px solid #eee;padding:6px;">
                            <form method="post" action="admin_update_role.php" class="inline" style="display:flex;gap:4px;align-items:center;">
                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                <select name="role">
                                    <option value="user"      <?= $u['role']==='user'?'selected':'' ?>>user</option>
                                    <option value="organizer" <?= $u['role']==='organizer'?'selected':'' ?>>organizer</option>
                                    <option value="admin"     <?= $u['role']==='admin'?'selected':'' ?>>admin</option>
                                </select>
                                <button type="submit" class="btn btn-secondary" style="padding:2px 6px;">OK</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <hr>

    <!-- DEMANDES ORGANISATEUR -->
    <h3>Demandes pour devenir organisateur</h3>

    <?php if (empty($requests)): ?>
        <p>Aucune demande en attente.</p>
    <?php else: ?>
        <table class="table" style="width:100%;border-collapse:collapse;margin-bottom:20px;">
            <thead>
                <tr>
                    <th style="border-bottom:1px solid #ddd;padding:6px;">ID</th>
                    <th style="border-bottom:1px solid #ddd;padding:6px;">Utilisateur</th>
                    <th style="border-bottom:1px solid #ddd;padding:6px;">Email</th>
                    <th style="border-bottom:1px solid #ddd;padding:6px;">Âge</th>
                    <th style="border-bottom:1px solid #ddd;padding:6px;">Détails</th>
                    <th style="border-bottom:1px solid #ddd;padding:6px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                    <?php
                    // On essaie de récupérer un texte descriptif quel que soit le nom de la colonne
                    $details = $r['details'] 
                        ?? ($r['message'] 
                        ?? ($r['experience'] 
                        ?? ''));
                    ?>
                    <tr>
                        <td style="border-bottom:1px solid #eee;padding:6px;"><?= (int)$r['id'] ?></td>
                        <td style="border-bottom:1px solid #eee;padding:6px;"><?= htmlspecialchars($r['user_name']) ?></td>
                        <td style="border-bottom:1px solid #eee;padding:6px;"><?= htmlspecialchars($r['user_email']) ?></td>
                        <td style="border-bottom:1px solid #eee;padding:6px;"><?= htmlspecialchars($r['user_age']) ?></td>
                        <td style="border-bottom:1px solid #eee;padding:6px;max-width:300px;">
                            <?= nl2br(htmlspecialchars($details)) ?>
                        </td>
                        <td style="border-bottom:1px solid #eee;padding:6px;">
                            <form method="post" action="admin_handle_request.php" class="inline" style="display:inline;">
                                <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn" style="margin-bottom:4px;">Accepter</button>
                            </form>
                            <form method="post" action="admin_handle_request.php" class="inline" style="display:inline;">
                                <input type="hidden" name="request_id" value="<?= (int)$r['id'] ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-danger">Refuser</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <hr>

    <!-- DERNIERS ÉVÉNEMENTS -->
    <h3>Derniers événements créés</h3>

    <?php if (empty($last_events)): ?>
        <p>Aucun événement.</p>
    <?php else: ?>
        <table class="table" style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="border-bottom:1px solid #ddd;padding:6px;">ID</th>
                    <th style="border-bottom:1px solid #ddd;padding:6px;">Titre</th>
                    <th style="border-bottom:1px solid #ddd;padding:6px;">Ville</th>
                    <th style="border-bottom:1px solid #ddd;padding:6px;">Catégorie</th>
                    <th style="border-bottom:1px solid #ddd;padding:6px;">Date</th>
                    <th style="border-bottom:1px solid #ddd;padding:6px;">Orga</th>
                    <th style="border-bottom:1px solid #ddd;padding:6px;">Inscrits / Capacité</th>
                    <th style="border-bottom:1px solid #ddd;padding:6px;">Âge min</th>
                    <th style="border-bottom:1px solid #ddd;padding:6px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($last_events as $e): ?>
                    <?php
                    $inscrits = count_inscriptions($pdo, $e['id']);
                    $capacity = (int)$e['capacity'];
                    ?>
                    <tr>
                        <td style="border-bottom:1px solid #eee;padding:6px;"><?= (int)$e['id'] ?></td>
                        <td style="border-bottom:1px solid #eee;padding:6px;"><?= htmlspecialchars($e['title']) ?></td>
                        <td style="border-bottom:1px solid #eee;padding:6px;"><?= htmlspecialchars($e['city']) ?></td>
                        <td style="border-bottom:1px solid #eee;padding:6px;"><?= htmlspecialchars($e['category']) ?></td>
                        <td style="border-bottom:1px solid #eee;padding:6px;">
                            <?= htmlspecialchars($e['date']) ?> <?= htmlspecialchars(substr($e['time'],0,5)) ?>
                        </td>
                        <td style="border-bottom:1px solid #eee;padding:6px;"><?= htmlspecialchars($e['organizer_name']) ?></td>
                        <td style="border-bottom:1px solid #eee;padding:6px;"><?= $inscrits ?>/<?= $capacity ?></td>
                        <td style="border-bottom:1px solid #eee;padding:6px;"><?= (int)$e['min_age'] ?>+</td>
                        <td style="border-bottom:1px solid #eee;padding:6px;">
                            <a href="event_detail.php?id=<?= (int)$e['id'] ?>" class="btn btn-secondary" style="padding:2px 6px;">Voir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</section>
</main>

</body>
</html>

