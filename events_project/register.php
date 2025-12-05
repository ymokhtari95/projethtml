<?php
session_start();
require_once 'config.php';
require_once 'email_verifier.php';
require_once 'secrets.php'; // 🔥 contient tes clés reCAPTCHA

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* =====================================================
       🔐 Vérification reCAPTCHA v3 côté serveur
    ======================================================*/
    if (!empty($_POST['recaptcha_token'])) {
        $token = $_POST['recaptcha_token'];
        $secretKey = RECAPTCHA_SECRET_KEY;
        $verifyURL = "https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$token";

        $response = file_get_contents($verifyURL);
        $responseData = json_decode($response, true);

        if (!$responseData['success'] || $responseData['score'] < 0.4) {
            $errors[] = "Vérification de sécurité reCAPTCHA échouée ❌";
        }
    } else {
        $errors[] = "Veuillez valider la sécurité reCAPTCHA.";
    }




    /* =====================================================
       ⚠️ Vérifications habituelles du formulaire
    ======================================================*/

    if (empty($errors)) {

        $name      = trim($_POST['name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = $_POST['password']  ?? '';
        $password2 = $_POST['password2'] ?? '';
        $age       = trim($_POST['age'] ?? '');

        if ($name === '') {
            $errors[] = "Le nom est obligatoire.";
        }

        if ($email === '') {
            $errors[] = "L'adresse email est obligatoire.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format d'email invalide.";
        }

        if ($password === '' || $password2 === '') {
            $errors[] = "Les deux mots de passe sont obligatoires.";
        } elseif ($password !== $password2) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        } elseif (strlen($password) < 6) {
            $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
        }

        if ($age === '' || !ctype_digit($age) || (int)$age < 0 || (int)$age > 120) {
            $errors[] = "Âge invalide.";
        }

        // Vérification si email existe déjà
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $errors[] = "Un compte existe déjà avec cet email.";
            }
        }

        // Vérification Hunter.io
        if (empty($errors)) {
            $hunter = verify_email_with_hunter($email);

            if ($hunter['success'] && !$hunter['is_ok']) {
                $errors[] = "Cette adresse email semble invalide (Hunter.io).";
            }
        }

        // Si tout est bon → on crée le compte
        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password_hash, role, age)
                VALUES (:name, :email, :password_hash, 'user', :age)
            ");
            $stmt->execute([
                ':name'          => $name,
                ':email'         => $email,
                ':password_hash' => $hash,
                ':age'           => (int)$age,
            ]);

            $success = "Compte créé avec succès 🎉 Vous pouvez vous connecter.";
            $name = $email = $age = "";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription</title>
    <link rel="stylesheet" href="style.css">
    
    <!-- reCAPTCHA v3 -->
    <script src="https://www.google.com/recaptcha/api.js?render=<?= RECAPTCHA_SITE_KEY ?>"></script>
    <script>
    function onSubmit() {
        grecaptcha.execute("<?= RECAPTCHA_SITE_KEY ?>", {action:"register"}).then(function(token){
            document.getElementById('recaptcha_token').value = token;
            document.getElementById('registerForm').submit();
        });
    }
    </script>
</head>
<body>

<header class="header">
    <h1 class="header-title">SYNAPZ – Inscription</h1>
    <nav class="header-nav">
        <a href="index.php">Accueil</a>
        <a href="events_list.php">Événements</a>
        <a href="login.php">Connexion</a>
        <a href="register.php">Inscription</a>
    </nav>
</header>

<main class="main">
<section class="card">

    <h2>Créer un compte</h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error"><ul>
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form id="registerForm" method="post" onsubmit="event.preventDefault(); onSubmit();">
        <input type="hidden" name="recaptcha_token" id="recaptcha_token">

        <div class="form-group"><label>Nom :</label>
            <input type="text" name="name" value="<?= htmlspecialchars($name ?? '') ?>" required>
        </div>

        <div class="form-group"><label>Email :</label>
            <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
        </div>

        <div class="form-group"><label>Âge :</label>
            <input type="number" name="age" min="0" max="120" value="<?= htmlspecialchars($age ?? '') ?>" required>
        </div>

        <div class="form-group"><label>Mot de passe :</label>
            <input type="password" name="password" required>
        </div>

        <div class="form-group"><label>Confirmer :</label>
            <input type="password" name="password2" required>
        </div>

        <button type="submit" class="btn">Créer mon compte</button>
    </form>

</section>
</main>

</body>
</html>



