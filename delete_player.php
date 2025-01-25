<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['id'])) {
    try {
        // Récupérer les informations du joueur pour vérifier qu'il appartient bien à l'équipe
        $stmt = $conn->prepare("SELECT * FROM players WHERE id = ? AND team_id = ?");
        $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
        $player = $stmt->fetch();

        if ($player) {
            // Supprimer la photo si elle existe
            if (!empty($player['photo_path']) && file_exists($player['photo_path'])) {
                unlink($player['photo_path']);
            }

            // Supprimer le joueur
            $stmt = $conn->prepare("DELETE FROM players WHERE id = ? AND team_id = ?");
            $stmt->execute([$_GET['id'], $_SESSION['user_id']]);

            $_SESSION['success'] = "Le joueur a été supprimé avec succès.";
        } else {
            $_SESSION['error'] = "Joueur non trouvé ou vous n'avez pas les droits pour le supprimer.";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

header('Location: players.php');
exit();
?> 