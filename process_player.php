<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Débogage - Afficher les données reçues
    error_log("Données POST reçues : " . print_r($_POST, true));
    error_log("ID de l'équipe : " . $_SESSION['user_id']);

    // Vérifier si tous les champs requis sont présents
    $required_fields = ['last_name', 'first_name', 'birth_date', 'birth_place', 'contact'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $_SESSION['error'] = "Tous les champs sont obligatoires.";
            error_log("Champ manquant : " . $field);
            header("Location: add_player.php");
            exit();
        }
    }

    // Vérifier si un fichier a été uploadé
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] == UPLOAD_ERR_NO_FILE) {
        $_SESSION['error'] = "La photo est obligatoire.";
        error_log("Erreur : Pas de fichier photo");
        header("Location: add_player.php");
        exit();
    }

    // Traiter l'upload de la photo
    $target_dir = "uploads/players/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION));
    $allowed_extensions = array("jpg", "jpeg", "png");
    
    if (!in_array($file_extension, $allowed_extensions)) {
        $_SESSION['error'] = "Seuls les fichiers JPG, JPEG et PNG sont autorisés.";
        error_log("Extension de fichier non autorisée : " . $file_extension);
        header("Location: add_player.php");
        exit();
    }

    // Générer un nom de fichier unique
    $photo_name = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $photo_name;

    error_log("Tentative d'upload du fichier vers : " . $target_file);

    if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
        try {
            // Insérer les données dans la base de données
            $sql = "INSERT INTO players (team_id, first_name, last_name, birth_date, birth_place, contact, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?)";
            error_log("SQL Query : " . $sql);
            
            $stmt = $conn->prepare($sql);
            
            $params = [
                $_SESSION['user_id'],
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['birth_date'],
                $_POST['birth_place'],
                $_POST['contact'],
                $target_file
            ];
            
            error_log("Paramètres de la requête : " . print_r($params, true));
            
            $result = $stmt->execute($params);
            error_log("Résultat de l'exécution : " . ($result ? "Succès" : "Échec"));

            if ($result) {
                $_SESSION['success'] = "Le joueur a été ajouté avec succès.";
                header("Location: players.php");
                exit();
            } else {
                throw new PDOException("Échec de l'insertion");
            }

        } catch(PDOException $e) {
            error_log("Erreur PDO : " . $e->getMessage());
            $_SESSION['error'] = "Erreur lors de l'ajout du joueur : " . $e->getMessage();
            // Supprimer la photo si l'insertion dans la base de données a échoué
            if (file_exists($target_file)) {
                unlink($target_file);
            }
            header("Location: add_player.php");
            exit();
        }
    } else {
        error_log("Échec de l'upload du fichier. Error code : " . $_FILES['photo']['error']);
        $_SESSION['error'] = "Erreur lors de l'upload de la photo.";
        header("Location: add_player.php");
        exit();
    }
} else {
    header("Location: add_player.php");
    exit();
}