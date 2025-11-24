<?php
require_once __DIR__ . '/../.env.php';

function get_db_connection() {
    try {
        $dsn = "pgsql:host=" . 'postgresql-groupea5.alwaysdata.net' . ";port=" . '5432' . ";dbname=" .'groupea5_bd';
        $pdo = new PDO($dsn, 'groupea5', 'yasser0048919');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }
}
?>