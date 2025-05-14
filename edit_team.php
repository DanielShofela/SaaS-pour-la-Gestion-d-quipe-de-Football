<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Récupérer les informations actuelles de l'équipe
try {
    $stmt = $conn->prepare("SELECT * FROM teams WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $team = $stmt->fetch();
} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Gestion de l'upload du logo si fourni
        $logo_path = $team['logo_path']; // Garde l'ancien chemin par défaut
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/logos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Supprimer l'ancien logo si il existe
            if (!empty($team['logo_path']) && file_exists($team['logo_path'])) {
                unlink($team['logo_path']);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $new_filename = uniqid() . '.' . $file_extension;
            $logo_path = $upload_dir . $new_filename;

            if (!move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path)) {
                throw new Exception("Erreur lors de l'upload du logo");
            }
        }

        $stmt = $conn->prepare("
            UPDATE teams 
            SET team_name = ?,
                locality = ?,
                coach_name = ?,
                division = ?,
                logo_path = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['team_name'],
            $_POST['locality'],
            $_POST['coach_name'],
            $_POST['division'],
            $logo_path,
            $_SESSION['user_id']
        ]);
        
        $_SESSION['team_name'] = $_POST['team_name']; // Mettre à jour le nom dans la session
        $_SESSION['success'] = "Les informations de l'équipe ont été mises à jour avec succès";
        header('Location: dashboard.php');
        exit();
    } catch(Exception $e) {
        $error = "Erreur lors de la modification : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'équipe - Football SAAS</title>
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
            <?php if (!empty($team['logo_path']) && file_exists($team['logo_path'])): ?>
                <img src="<?= htmlspecialchars($team['logo_path']) ?>" alt="Logo équipe" class="profile-image">
            <?php else: ?>
                <img src="assets/images/team-logo-placeholder.png" alt="Logo équipe" class="profile-image">
            <?php endif; ?>
            <h5><?= htmlspecialchars($team['team_name']) ?></h5>
            <p class="text-muted mb-0">Division <?= htmlspecialchars($team['division']) ?></p>
        </div>
        <div class="nav flex-column">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-home me-2"></i>Tableau de bord
            </a>
            <a href="players.php" class="nav-link">
                <i class="fas fa-users me-2"></i>Joueurs
            </a>
            <a href="matches.php" class="nav-link">
                <i class="fas fa-futbol me-2"></i>Matchs
            </a>
            <a href="statistics.php" class="nav-link">
                <i class="fas fa-chart-bar me-2"></i>Statistiques
            </a>
            <a href="settings.php" class="nav-link active">
                <i class="fas fa-cog me-2"></i>Paramètres
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="form-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="fas fa-edit me-2"></i>Modifier les informations de l'équipe
                </h2>
                <a href="dashboard.php" class="btn btn-outline-secondary btn-custom">
                    <i class="fas fa-arrow-left me-2"></i>Retour au tableau de bord
                </a>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="mt-4">
                <div class="row g-4">
                    <!-- Informations de base -->
                    <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-info-circle me-2"></i>Informations de base
                                </h5>
                                <div class="mb-3">
                                    <label class="form-label">Nom de l'équipe</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-shield-alt"></i>
                                        </span>
                                        <input type="text" name="team_name" class="form-control" 
                                               value="<?= htmlspecialchars($team['team_name']) ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Localité</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </span>
                                        <input type="text" name="locality" class="form-control" 
                                               value="<?= htmlspecialchars($team['locality']) ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Informations sportives -->
                    <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-trophy me-2"></i>Informations sportives
                                </h5>
                                <div class="mb-3">
                                    <label class="form-label">Nom du coach</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-user-tie"></i>
                                        </span>
                                        <input type="text" name="coach_name" class="form-control" 
                                               value="<?= htmlspecialchars($team['coach_name']) ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Division</label>
                                    <select name="division" class="form-control" required>
                                        <?php for($i = 1; $i <= 4; $i++): ?>
                                            <option value="<?= $i ?>" <?= $team['division'] == $i ? 'selected' : '' ?>>
                                                Division <?= $i ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Logo de l'équipe -->
                    <div class="col-12">
                        <div class="card dashboard-card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-image me-2"></i>Logo de l'équipe
                                </h5>
                                <?php if (!empty($team['logo_path'])): ?>
                                    <div class="text-center mb-3">
                                        <img src="<?= htmlspecialchars($team['logo_path']) ?>" 
                                             alt="Logo actuel" class="img-thumbnail" style="max-width: 150px;">
                                        <p class="text-muted mt-2">Logo actuel</p>
                                    </div>
                                <?php endif; ?>
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-upload"></i>
                                        </span>
                                        <input type="file" name="logo" class="form-control" accept="image/*">
                                    </div>
                                    <small class="text-muted">Format recommandé : JPG, PNG (max 2MB)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <button type="submit" class="btn btn-primary btn-custom">
                        <i class="fas fa-save me-2"></i>Enregistrer les modifications
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary btn-custom ms-2">
                        <i class="fas fa-times me-2"></i>Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 