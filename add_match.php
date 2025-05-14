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

    // Récupérer le prochain match programmé
    $stmt = $conn->prepare("
        SELECT MIN(match_date) as next_date 
        FROM matches 
        WHERE match_date > NOW() 
        AND (home_team_id = ? OR away_team_id = ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $next_match = $stmt->fetch();
    
    // Suggérer une date par défaut (prochain samedi à 15h)
    $suggested_date = date('Y-m-d\TH:i', strtotime('next saturday 15:00'));
    if ($next_match && $next_match['next_date']) {
        // Suggérer une date une semaine après le dernier match programmé
        $suggested_date = date('Y-m-d\TH:i', strtotime($next_match['next_date'] . ' +1 week'));
    }
} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Vérifier que la date n'est pas dans le passé
        if (strtotime($_POST['match_date']) < time()) {
            throw new Exception("La date du match ne peut pas être dans le passé");
        }

        // Vérifier qu'il n'y a pas déjà un match à cette date
        $stmt = $conn->prepare("
            SELECT id FROM matches 
            WHERE match_date = ? 
            AND (home_team_id = ? OR away_team_id = ?)
        ");
        $stmt->execute([$_POST['match_date'], $_SESSION['user_id'], $_SESSION['user_id']]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Vous avez déjà un match programmé à cette date");
        }

        $stmt = $conn->prepare("
            INSERT INTO matches (
                home_team_id, away_team_id, away_team_name, 
                match_date, status
            ) VALUES (?, ?, ?, ?, 'scheduled')
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            0,
            $_POST['away_team_name'],
            $_POST['match_date']
        ]);
        
        $_SESSION['success'] = "Le match a été ajouté avec succès";
        header('Location: matches.php');
        exit();
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programmer un Match - Football SAAS</title>
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
            <a href="matches.php" class="nav-link active">
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
                    <i class="fas fa-plus me-2"></i>Programmer un Match
                </h2>
                <a href="matches.php" class="btn btn-outline-secondary btn-custom">
                    <i class="fas fa-arrow-left me-2"></i>Retour aux matchs
                </a>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="mt-4">
                <div class="row g-4">
                    <!-- Équipe domicile -->
                    <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-home me-2"></i>Équipe Domicile
                                </h5>
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-shield-alt"></i>
                                        </span>
                                        <input type="text" class="form-control" 
                                               value="<?= htmlspecialchars($team['team_name']) ?>" readonly>
                                    </div>
                                    <small class="text-muted">Votre équipe</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Équipe extérieur -->
                    <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-plane me-2"></i>Équipe Extérieur
                                </h5>
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-shield-alt"></i>
                                        </span>
                                        <input type="text" name="away_team_name" class="form-control" 
                                               placeholder="Nom de l'équipe adverse" required>
                                    </div>
                                    <small class="text-muted">Entrez le nom de l'équipe adverse</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Date et heure -->
                    <div class="col-12">
                        <div class="card dashboard-card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="far fa-calendar-alt me-2"></i>Date et Heure
                                </h5>
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="far fa-clock"></i>
                                        </span>
                                        <input type="datetime-local" name="match_date" class="form-control" 
                                               value="<?= $suggested_date ?>" required>
                                    </div>
                                    <small class="text-muted">Date et heure suggérées pour le prochain match</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <button type="submit" class="btn btn-primary btn-custom">
                        <i class="fas fa-save me-2"></i>Programmer le match
                    </button>
                    <a href="matches.php" class="btn btn-secondary btn-custom ms-2">
                        <i class="fas fa-times me-2"></i>Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 