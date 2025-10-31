<?php
try {
    // Tentative de connexion locale
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=essaie', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    try {
        // Tentative de connexion sur l'hébergeur
        $pdo = new PDO('mysql:host=sql100.infinityfree.com;dbname=if0_39578808_essai', 'if0_39578808', 'zr3XpcNfOcava');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
        // Erreur seulement si les DEUX connexions échouent
        die("Erreur de connexion : " . $e->getMessage());
    }
}
?>