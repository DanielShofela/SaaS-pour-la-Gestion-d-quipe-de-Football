<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $team_name = htmlspecialchars($_POST['team_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $locality = htmlspecialchars($_POST['locality'] ?? '', ENT_QUOTES, 'UTF-8');
    $coach_name = htmlspecialchars($_POST['coach_name'] ?? '', ENT_QUOTES, 'UTF-8');
    $division = filter_input(INPUT_POST, 'division', FILTER_SANITIZE_NUMBER_INT);

    // Traiter l'upload de la photo
    $target_dir = __DIR__ . "/uploads/players/";
    error_log("Chemin complet du dossier d'upload : " . $target_dir);

    if (!file_exists($target_dir)) {
        error_log("Création du dossier d'upload...");
        $created = mkdir($target_dir, 0777, true);
        error_log("Résultat de la création du dossier : " . ($created ? "Succès" : "Échec"));
        
        if ($created) {
            // S'assurer que les permissions sont correctes
            chmod($target_dir, 0777);
            error_log("Permissions du dossier mises à jour");
        }
    }

    // Vérifier si le dossier est accessible en écriture
    if (!is_writable($target_dir)) {
        error_log("Le dossier n'est pas accessible en écriture : " . $target_dir);
        $_SESSION['error'] = "Erreur de configuration : le dossier d'upload n'est pas accessible en écriture.";
        header("Location: add_player.php");
        exit();
    }

    // Check if a file was actually uploaded
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file_info = pathinfo($_FILES['photo']['name']);
        $file_extension = strtolower($file_info['extension']);
        
        // Validate file extension (you might want to add more allowed extensions)
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_extension, $allowed_extensions)) {
            $_SESSION['error'] = "Type de fichier non autorisé. Seuls les formats JPG, JPEG, PNG et GIF sont acceptés.";
            header("Location: add_player.php");
            exit();
        }

        $photo_name = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $photo_name;

        error_log("Tentative d'upload du fichier vers : " . $target_file);
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
            // File upload success - continue with the rest of your code
        } else {
            error_log("Échec de l'upload. Erreur PHP : " . error_get_last()['message']);
            $_SESSION['error'] = "Erreur lors de l'upload du fichier.";
            header("Location: add_player.php");
            exit();
        }
    } else {
        // No file was uploaded or there was an error
        $photo_name = ''; // or set a default image name
    }

    // Validation
    if ($password !== $confirm_password) {
        die("Les mots de passe ne correspondent pas");
    }

    // Hash du mot de passe avec MD5
    $hashed_password = md5($password);

    try {
        // Vérifier si l'email existe déjà
        $stmt = $conn->prepare("SELECT id FROM teams WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            die("Cet email est déjà utilisé");
        }

        // Insérer la nouvelle équipe
        $stmt = $conn->prepare("INSERT INTO teams (email, password, team_name, locality, coach_name, division) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$email, $hashed_password, $team_name, $locality, $coach_name, $division]);

        // Redirection vers la page de connexion
        header("Location: login.php?registration=success");
        exit();
    } catch(PDOException $e) {
        die("Erreur d'inscription : " . $e->getMessage());
    }
}
?>
