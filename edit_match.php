<<<<<<< HEAD
<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Récupérer les informations de l'équipe connectée
$stmt = $conn->prepare("SELECT * FROM teams WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$team = $stmt->fetch();

// Récupérer les informations du match
if (isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM matches WHERE id = ? AND (home_team_id = ? OR away_team_id = ?)");
        $stmt->execute([$_GET['id'], $_SESSION['user_id'], $_SESSION['user_id']]);
        $match = $stmt->fetch();
        
        if (!$match) {
            header('Location: matches.php');
            exit();
        }
    } catch(PDOException $e) {
        die("Erreur : " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $conn->prepare("
            UPDATE matches 
            SET away_team_name = ?,
                match_date = ?,
                home_score = ?,
                away_score = ?,
                status = ?
            WHERE id = ? AND (home_team_id = ? OR away_team_id = ?)
        ");
        
        $stmt->execute([
            $_POST['away_team_name'],
            $_POST['match_date'],
            $_POST['home_score'],
            $_POST['away_score'],
            $_POST['status'],
            $_GET['id'],
            $_SESSION['user_id'],
            $_SESSION['user_id']
        ]);
        
        header('Location: matches.php');
        exit();
    } catch(PDOException $e) {
        $error = "Erreur lors de la modification du match : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Match - Football SAAS</title>
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
            <a href="players.php" class="nav-link">
                <i class="fas fa-users me-2"></i>Joueurs
            </a>
            <a href="matches.php" class="nav-link active">
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
                <i class="fas fa-edit me-2"></i>Modifier le Match
            </h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="mt-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-home me-2"></i>Équipe Domicile
                                </h5>
                                <div class="mb-3">
                                    <input type="text" class="form-control" 
                                           value="<?= htmlspecialchars($team['team_name']) ?>" readonly>
                                    <small class="text-muted">Votre équipe</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Score Domicile</label>
                                    <input type="number" name="home_score" class="form-control" 
                                           value="<?= $match['home_score'] ?>" min="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-plane me-2"></i>Équipe Extérieur
                                </h5>
                                <div class="mb-3">
                                    <input type="text" name="away_team_name" class="form-control" 
                                           value="<?= htmlspecialchars($match['away_team_name'] ?? '') ?>" required>
                                    <small class="text-muted">Nom de l'équipe adverse</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Score Extérieur</label>
                                    <input type="number" name="away_score" class="form-control" 
                                           value="<?= $match['away_score'] ?>" min="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="far fa-calendar-alt me-2"></i>Date et Heure
                                </h5>
                                <div class="mb-3">
                                    <input type="datetime-local" name="match_date" class="form-control" 
                                           value="<?= date('Y-m-d\TH:i', strtotime($match['match_date'])) ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-flag me-2"></i>Statut
                                </h5>
                                <div class="mb-3">
                                    <select name="status" class="form-control" required>
                                        <option value="scheduled" <?= $match['status'] == 'scheduled' ? 'selected' : '' ?>>
                                            Programmé
                                        </option>
                                        <option value="in_progress" <?= $match['status'] == 'in_progress' ? 'selected' : '' ?>>
                                            En cours
                                        </option>
                                        <option value="completed" <?= $match['status'] == 'completed' ? 'selected' : '' ?>>
                                            Terminé
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <button type="submit" class="btn btn-primary btn-custom">
                        <i class="fas fa-save me-2"></i>Enregistrer les modifications
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
=======
<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Récupérer les informations de l'équipe connectée
$stmt = $conn->prepare("SELECT * FROM teams WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$team = $stmt->fetch();

// Récupérer les informations du match
if (isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM matches WHERE id = ? AND (home_team_id = ? OR away_team_id = ?)");
        $stmt->execute([$_GET['id'], $_SESSION['user_id'], $_SESSION['user_id']]);
        $match = $stmt->fetch();
        
        if (!$match) {
            header('Location: matches.php');
            exit();
        }
    } catch(PDOException $e) {
        die("Erreur : " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $conn->prepare("
            UPDATE matches 
            SET away_team_name = ?,
                match_date = ?,
                home_score = ?,
                away_score = ?,
                status = ?
            WHERE id = ? AND (home_team_id = ? OR away_team_id = ?)
        ");
        
        $stmt->execute([
            $_POST['away_team_name'],
            $_POST['match_date'],
            $_POST['home_score'],
            $_POST['away_score'],
            $_POST['status'],
            $_GET['id'],
            $_SESSION['user_id'],
            $_SESSION['user_id']
        ]);
        
        header('Location: matches.php');
        exit();
    } catch(PDOException $e) {
        $error = "Erreur lors de la modification du match : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Match - Football SAAS</title>
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
            <a href="players.php" class="nav-link">
                <i class="fas fa-users me-2"></i>Joueurs
            </a>
            <a href="matches.php" class="nav-link active">
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
                <i class="fas fa-edit me-2"></i>Modifier le Match
            </h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="mt-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-home me-2"></i>Équipe Domicile
                                </h5>
                                <div class="mb-3">
                                    <input type="text" class="form-control" 
                                           value="<?= htmlspecialchars($team['team_name']) ?>" readonly>
                                    <small class="text-muted">Votre équipe</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Score Domicile</label>
                                    <input type="number" name="home_score" class="form-control" 
                                           value="<?= $match['home_score'] ?>" min="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-plane me-2"></i>Équipe Extérieur
                                </h5>
                                <div class="mb-3">
                                    <input type="text" name="away_team_name" class="form-control" 
                                           value="<?= htmlspecialchars($match['away_team_name'] ?? '') ?>" required>
                                    <small class="text-muted">Nom de l'équipe adverse</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Score Extérieur</label>
                                    <input type="number" name="away_score" class="form-control" 
                                           value="<?= $match['away_score'] ?>" min="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="far fa-calendar-alt me-2"></i>Date et Heure
                                </h5>
                                <div class="mb-3">
                                    <input type="datetime-local" name="match_date" class="form-control" 
                                           value="<?= date('Y-m-d\TH:i', strtotime($match['match_date'])) ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card dashboard-card">
                            <div class="card-body">
                                <h5 class="card-title mb-3">
                                    <i class="fas fa-flag me-2"></i>Statut
                                </h5>
                                <div class="mb-3">
                                    <select name="status" class="form-control" required>
                                        <option value="scheduled" <?= $match['status'] == 'scheduled' ? 'selected' : '' ?>>
                                            Programmé
                                        </option>
                                        <option value="in_progress" <?= $match['status'] == 'in_progress' ? 'selected' : '' ?>>
                                            En cours
                                        </option>
                                        <option value="completed" <?= $match['status'] == 'completed' ? 'selected' : '' ?>>
                                            Terminé
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <button type="submit" class="btn btn-primary btn-custom">
                        <i class="fas fa-save me-2"></i>Enregistrer les modifications
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
>>>>>>> a9acff600a16e9503acb7cdb1744fd80ae22878d
</html> 