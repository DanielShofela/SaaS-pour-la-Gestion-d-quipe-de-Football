<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Récupérer les informations de l'équipe
$stmt = $conn->prepare("SELECT * FROM teams WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$team = $stmt->fetch();

// Récupérer les informations du joueur
if (isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM players WHERE id = ? AND team_id = ?");
        $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
        $player = $stmt->fetch();
        
        if (!$player) {
            $_SESSION['error'] = "Joueur non trouvé ou vous n'avez pas les droits pour le modifier.";
            header('Location: players.php');
            exit();
        }
    } catch(PDOException $e) {
        die("Erreur : " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Gestion de la nouvelle photo si uploadée
        $photo_path = $player['photo_path'];
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/players/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Supprimer l'ancienne photo si elle existe
            if (!empty($player['photo_path']) && file_exists($player['photo_path'])) {
                unlink($player['photo_path']);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $new_filename = uniqid() . '.' . $file_extension;
            $photo_path = $upload_dir . $new_filename;

            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
                throw new Exception("Erreur lors de l'upload de la photo");
            }
        }

        $stmt = $conn->prepare("
            UPDATE players 
            SET first_name = ?, 
                last_name = ?, 
                birth_date = ?,
                position = ?,
                jersey_number = ?,
                contact = ?,
                photo_path = ?
            WHERE id = ? AND team_id = ?
        ");
        
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['birth_date'],
            $_POST['position'],
            $_POST['jersey_number'],
            $_POST['contact'],
            $photo_path,
            $_GET['id'],
            $_SESSION['user_id']
        ]);
        
        $_SESSION['success'] = "Le joueur a été modifié avec succès.";
        header('Location: players.php');
        exit();
    } catch(Exception $e) {
        $error = "Erreur lors de la modification du joueur : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Joueur - Football SAAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-futbol me-2"></i>Football SAAS
            </a>
            <div class="d-flex">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-2"></i><?= htmlspecialchars($team['team_name']) ?>
                </span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="profile-section">
            <img src="assets/images/team-logo-placeholder.png" alt="Logo équipe" class="profile-image">
            <h5><?= htmlspecialchars($team['team_name']) ?></h5>
            <p class="text-muted mb-0">Division <?= htmlspecialchars($team['division']) ?></p>
        </div>
        <div class="nav flex-column">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-home me-2"></i>Tableau de bord
            </a>
            <a href="players.php" class="nav-link active">
                <i class="fas fa-users me-2"></i>Joueurs
            </a>
            <a href="matches.php" class="nav-link">
                <i class="fas fa-futbol me-2"></i>Matchs
            </a>
            <a href="settings.php" class="nav-link">
                <i class="fas fa-cog me-2"></i>Paramètres
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="form-container">
            <h2 class="mb-4">
                <i class="fas fa-user-edit me-2"></i>Modifier le Joueur
            </h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="mt-4">
                <div class="row g-4">
                    <!-- Informations personnelles -->
                    <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-user me-2"></i>Informations Personnelles
                                </h5>
                                <div class="mb-3">
                                    <label class="form-label">Prénom</label>
                                    <input type="text" name="first_name" class="form-control" 
                                           value="<?= htmlspecialchars($player['first_name']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nom</label>
                                    <input type="text" name="last_name" class="form-control" 
                                           value="<?= htmlspecialchars($player['last_name']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Date de naissance</label>
                                    <input type="date" name="birth_date" class="form-control" 
                                           value="<?= $player['birth_date'] ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Contact</label>
                                    <input type="tel" name="contact" class="form-control" 
                                           value="<?= htmlspecialchars($player['contact']) ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informations sportives -->
                    <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-futbol me-2"></i>Informations Sportives
                                </h5>
                                <div class="mb-3">
                                    <label class="form-label">Position</label>
                                    <select name="position" class="form-control" required>
                                        <option value="Gardien" <?= $player['position'] == 'Gardien' ? 'selected' : '' ?>>Gardien</option>
                                        <option value="Défenseur" <?= $player['position'] == 'Défenseur' ? 'selected' : '' ?>>Défenseur</option>
                                        <option value="Milieu" <?= $player['position'] == 'Milieu' ? 'selected' : '' ?>>Milieu</option>
                                        <option value="Attaquant" <?= $player['position'] == 'Attaquant' ? 'selected' : '' ?>>Attaquant</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Numéro de maillot</label>
                                    <input type="number" name="jersey_number" class="form-control" 
                                           value="<?= $player['jersey_number'] ?>" min="1" max="99" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Photo</label>
                                    <?php if (!empty($player['photo_path'])): ?>
                                        <div class="mb-2">
                                            <img src="<?= htmlspecialchars($player['photo_path']) ?>" 
                                                 alt="Photo actuelle" class="img-thumbnail" style="max-width: 100px;">
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" name="photo" class="form-control" accept="image/*">
                                    <small class="text-muted">Laissez vide pour conserver la photo actuelle</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <button type="submit" class="btn btn-primary btn-custom">
                        <i class="fas fa-save me-2"></i>Enregistrer les modifications
                    </button>
                    <a href="players.php" class="btn btn-secondary btn-custom ms-2">
                        <i class="fas fa-times me-2"></i>Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 