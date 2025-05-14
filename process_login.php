<?php
session_start();
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = md5($_POST['password']); // Hash du mot de passe pour la comparaison

    try {
        $stmt = $conn->prepare("SELECT id, team_name FROM teams WHERE email = ? AND password = ?");
        $stmt->execute([$email, $password]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['team_name'] = $user['team_name'];
            
            header("Location: dashboard.php");
            exit();
        } else {
            header("Location: login.php?error=invalid");
            exit();
        }
    } catch(PDOException $e) {
        die("Erreur de connexion : " . $e->getMessage());
    }
}
?>
