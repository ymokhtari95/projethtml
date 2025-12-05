<?php 
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - SYNAPZ</title>
    <link rel="stylesheet" href="style.css?v=3">
</head>
<body>

<header class="header">
    <div class="header-title">
        <img src="assets/logo/synapz_logo.png" alt="SYNAPZ" class="logo-synapz">
    </div>

    <nav class="header-nav">
        <a href="events_list.php">Événements</a>

        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="my_events.php">Mes inscriptions</a>

            <?php if ($_SESSION['user_role'] === 'organizer' || $_SESSION['user_role'] === 'admin'): ?>
                <a href="my_created_events.php">Mes événements créés</a>
                <a href="create_event.php">Créer un événement</a>
            <?php endif; ?>

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

    <!-- Bloc de bienvenue -->
    <section class="card">
        <h2>Bienvenue sur la plateforme d'événements</h2>

        <?php if (isset($_SESSION['user_id'])): ?>
            <p>
                Connecté en tant que
                <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                <span class="badge badge-blue"><?= htmlspecialchars($_SESSION['user_role']) ?></span>
            </p>

            <p class="meta">
                Utilise le menu en haut pour créer ou gérer tes événements,
                ou pour voir ceux auxquels tu es inscrit.
            </p>

        <?php else: ?>
            <p>
                Connecte-toi ou crée un compte pour t'inscrire à des événements,
                ou en organiser.
            </p>
            <div style="margin-top:10px;">
                <a class="btn" href="login.php">Se connecter</a>
                <a class="btn btn-secondary" href="register.php">Créer un compte</a>
            </div>
        <?php endif; ?>
    </section>

    <!-- Accès rapide à la liste complète -->
    <section class="card" style="margin-top:16px;">
        <h3>Explorer tous les événements</h3>
        <p class="meta">Consulte la liste complète des événements disponibles, tous formats confondus.</p>
        <a class="btn inline" href="events_list.php" style="margin-top:8px;">Voir les événements</a>
    </section>

    <!-- SECTION CATEGORIES FAÇON NIKE -->
    <section style="margin-top:26px;">
        <h2 style="font-size:1.3rem; margin-bottom:4px;">Explorer par catégorie</h2>
        <p class="meta" style="margin-bottom:12px;">
            Choisis une ambiance, on s’occupe du reste.
        </p>

        <div class="categories-grid">

            <!-- Soirée -->
            <a href="events_list.php?category=Soirée"
               class="category-block"
               style="background-image: url('assets/categories/soiree_neon.jpg');">
                <div class="category-content">
                    <div class="category-label">Catégorie</div>
                    <div class="category-title">Soirées & nightlife</div>
                    <div class="category-desc">
                        Clubs, rooftops, vibes néon et nuits à rallonge.
                    </div>
                    <button class="category-btn">Voir les soirées</button>
                </div>
            </a>

            <!-- Sport -->
            <a href="events_list.php?category=Sport"
               class="category-block"
               style="background-image: url('assets/categories/sport_gym.jpg');">
                <div class="category-content">
                    <div class="category-label">Catégorie</div>
                    <div class="category-title">Sport & performance</div>
                    <div class="category-desc">
                        Tournois, matchs, sessions training et défis.
                    </div>
                    <button class="category-btn">Voir le sport</button>
                </div>
            </a>

            <!-- Conférence -->
            <a href="events_list.php?category=Conférence"
               class="category-block"
               style="background-image: url('assets/categories/conference_buisness.jpg');">
                <div class="category-content">
                    <div class="category-label">Catégorie</div>
                    <div class="category-title">Conférences & talks</div>
                    <div class="category-desc">
                        Keynotes, tables rondes, business & inspiration.
                    </div>
                    <button class="category-btn">Voir les conférences</button>
                </div>
            </a>

            <!-- Meetup -->
            <a href="events_list.php?category=Meetup"
               class="category-block"
               style="background-image: url('assets/categories/meetup_cafe.jpg');">
                <div class="category-content">
                    <div class="category-label">Catégorie</div>
                    <div class="category-title">Meetups & networking</div>
                    <div class="category-desc">
                        Rencontres, échanges, communautés et nouveaux contacts.
                    </div>
                    <button class="category-btn">Voir les meetups</button>
                </div>
            </a>

            <!-- Concert -->
            <a href="events_list.php?category=Concert"
               class="category-block"
               style="background-image: url('assets/categories/concert_crowd.jpg');">
                <div class="category-content">
                    <div class="category-label">Catégorie</div>
                    <div class="category-title">Concerts & festivals</div>
                    <div class="category-desc">
                        Scènes, lumières, confettis et décibels.
                    </div>
                    <button class="category-btn">Voir les concerts</button>
                </div>
            </a>

            <!-- Gaming -->
            <a href="events_list.php?category=Gaming"
               class="category-block"
               style="background-image: url('assets/categories/gaming_setup.jpg');">
                <div class="category-content">
                    <div class="category-label">Catégorie</div>
                    <div class="category-title">Gaming & e-sport</div>
                    <div class="category-desc">
                        LAN, tournois, soirées ranked et events e-sport.
                    </div>
                    <button class="category-btn">Voir le gaming</button>
                </div>
            </a>

            <!-- Atelier -->
            <a href="events_list.php?category=Atelier"
               class="category-block"
               style="background-image: url('assets/categories/atelier_poterie.jpg');">
                <div class="category-content">
                    <div class="category-label">Catégorie</div>
                    <div class="category-title">Ateliers & créativité</div>
                    <div class="category-desc">
                        Poterie, dessin, DIY, cuisine, tout ce qui se crée.
                    </div>
                    <button class="category-btn">Voir les ateliers</button>
                </div>
            </a>

        </div>
    </section>

</main>

</body>
</html>

