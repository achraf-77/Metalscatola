<?php

$host = '127.0.0.1';
$dbname = 'Metalscatola_afrique';
$user = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOEXCEPTION $e) {
    die("Erreur de connexion:" . $e->getMessage());
}
