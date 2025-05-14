<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM matches WHERE id = ?");
        $stmt->execute([$_GET['id']]);
    } catch(PDOException $e) {
        die("Erreur lors de la suppression : " . $e->getMessage());
    }
}

header('Location: matches.php');
exit();
?> 