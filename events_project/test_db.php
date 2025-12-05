<?php
require 'config.php';

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<h2>Connexion réussie !</h2>";
    echo "<pre>";
    print_r($tables);
    echo "</pre>";
} catch (PDOException $e) {
    echo "Erreur SQL : " . $e->getMessage();
}
?>
