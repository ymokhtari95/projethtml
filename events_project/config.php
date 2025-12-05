<?php 
$host = "mysql-synapz.alwaysdata.net";  // hôte MySQL Alwaysdata
$dbname = "synapz_db";                  // nom de ta base
$username = "synapz";                   // utilisateur MySQL
$password = "Yassine2005*";               // mot de passe MySQL

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
