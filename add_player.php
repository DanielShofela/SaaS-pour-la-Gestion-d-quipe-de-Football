<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Récupérer les informations de l'équipe
try {
    $stmt = $conn->prepare("SELECT * FROM teams WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $team = $stmt->fetch();

    // Récupérer le dernier numéro de maillot utilisé
    $stmt = $conn->prepare("SELECT MAX(jersey_number) as max_number FROM players WHERE team_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    $suggested_number = ($result['max_number'] ?? 0) + 1;
} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Gestion de l'upload de la photo
        $photo_path = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/players/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $new_filename = uniqid() . '.' . $file_extension;
            $photo_path = $upload_dir . $new_filename;

            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
                throw new Exception("Erreur lors de l'upload de la photo");
            }
        }

        $stmt = $conn->prepare("
            INSERT INTO players (
                team_id, first_name, last_name, birth_date, 
                position, jersey_number, contact, photo_path
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['birth_date'],
            $_POST['position'],
            $_POST['jersey_number'],
            $_POST['contact'],
            $photo_path
        ]);
        
        $_SESSION['success'] = "Le joueur a été ajouté avec succès";
        header('Location: players.php');
        exit();
    } catch(Exception $e) {
        $error = "Erreur lors de l'ajout du joueur : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Joueur - Football SAAS</title>
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
            <a href="players.php" class="nav-link active">
                <i class="fas fa-users me-2"></i>Joueurs
            </a>
            <a href="matches.php" class="nav-link">
                <i class="fas fa-futbol me-2"></i>Matchs
            </a>
            <a href="statistics.php" class="nav-link">
                <i class="fas fa-chart-bar me-2"></i>Statistiques
            </a>
            <a href="settings.php" class="nav-link">
                <i class="fas fa-cog me-2"></i>Paramètres
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="form-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="fas fa-user-plus me-2"></i>Ajouter un Joueur
                </h2>
                <a href="players.php" class="btn btn-outline-secondary btn-custom">
                    <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                </a>
            </div>
            
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
                                    <input type="text" name="first_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nom</label>
                                    <input type="text" name="last_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Date de naissance</label>
                                    <input type="date" name="birth_date" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Contact</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-phone"></i>
                                        </span>
                                        <input type="tel" name="contact" class="form-control" required>
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
                                    <i class="fas fa-futbol me-2"></i>Informations Sportives
                                </h5>
                                <div class="mb-3">
                                    <label class="form-label">Position</label>
                                    <select name="position" class="form-control" required>
                                        <option value="">Sélectionnez une position</option>
                                        <option value="Gardien">Gardien</option>
                                        <option value="Défenseur">Défenseur</option>
                                        <option value="Milieu">Milieu</option>
                                        <option value="Attaquant">Attaquant</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Numéro de maillot</label>
                                    <input type="number" name="jersey_number" class="form-control" 
                                           min="1" max="99" value="<?= $suggested_number ?>" required>
                                    <small class="text-muted">Prochain numéro disponible suggéré</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Photo</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-camera"></i>
                                        </span>
                                        <input type="file" name="photo" class="form-control" accept="image/*">
                                    </div>
                                    <small class="text-muted">Format recommandé : JPG, PNG (max 2MB)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <button type="submit" class="btn btn-primary btn-custom">
                        <i class="fas fa-save me-2"></i>Enregistrer le joueur
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