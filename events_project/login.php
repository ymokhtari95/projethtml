<?php
session_start();
require_once 'config.php';
require_once 'secrets.php'; // pour RECAPTCHA_SITE_KEY et RECAPTCHA_SECRET_KEY

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    /* ----------------- Vérification reCAPTCHA v3 ----------------- */
    $recaptchaToken = $_POST['recaptcha_token'] ?? null;

    if (!$recaptchaToken) {
        $errors[] = "Vérification de sécurité (captcha) manquante.";
    } else {
        $secret = RECAPTCHA_SECRET_KEY;

        $verifyResponse = file_get_contents(
            "https://www.google.com/recaptcha/api/siteverify?secret=" .
            urlencode($secret) .
            "&response=" . urlencode($recaptchaToken)
        );

        $captchaData = json_decode($verifyResponse, true);

        // On vérifie le succès + un score minimum
        if (
            empty($captchaData['success']) ||
            !isset($captchaData['score']) ||
            $captchaData['score'] < 0.5
        ) {
            $errors[] = "Vérification de sécurité échouée. Veuillez réessayer.";
        }
    }
    /* ------------------------------------------------------------- */

    // Validation classique du formulaire seulement si pas d'erreur captcha
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email invalide.";
    }

    if ($password === '') {
        $errors[] = "Mot de passe obligatoire.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = "Email ou mot de passe incorrect.";
        } else {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - SYNAPZ</title>
    <link rel="stylesheet" href="style.css?v=4">
    <!-- Script reCAPTCHA v3 -->
    <script src="https://www.google.com/recaptcha/api.js?render=<?= RECAPTCHA_SITE_KEY ?>"></script>
</head>
<body>

<header class="header">
    <div class="header-title">
        <img src="assets/logo/synapz_logo.png" alt="SYNAPZ" class="logo-synapz">
    </div>
    <nav class="header-nav">
        <a href="index.php">Accueil</a>
        <a href="events_list.php">Événements</a>
        <a href="register.php">Inscription</a>
    </nav>
</header>

<main class="main">
    <section class="card">
        <h2>Connexion</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="email">Email :</label>
                <input type="email" name="email" id="email" required
                       value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe :</label>
                <input type="password" name="password" id="password" required>
            </div>

            <!-- Token reCAPTCHA v3 -->
            <input type="hidden" name="recaptcha_token" id="recaptcha_token">

            <button type="submit" class="btn">Se connecter</button>
        </form>

        <p class="meta" style="margin-top:10px;">
            Pas encore de compte ? <a href="register.php">Créer un compte</a>
        </p>
    </section>
</main>

<script>
grecaptcha.ready(function () {
    grecaptcha.execute("<?= RECAPTCHA_SITE_KEY ?>", {action: "login"})
        .then(function (token) {
            document.getElementById('recaptcha_token').value = token;
        });
});
</script>

</body>
</html>
