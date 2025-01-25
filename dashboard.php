<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

try {
    // Récupérer les informations de l'équipe
    $stmt = $conn->prepare("SELECT * FROM teams WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $team = $stmt->fetch();

    // Récupérer les statistiques
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM players WHERE team_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $playerStats = $stmt->fetch();

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM matches WHERE home_team_id = ? OR away_team_id = ?");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $matchStats = $stmt->fetch();
} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Football SAAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
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
            <a href="dashboard.php" class="nav-link active">
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
            <a href="settings.php" class="nav-link">
                <i class="fas fa-cog me-2"></i>Paramètres
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="row g-4">
            <!-- Statistiques rapides -->
            <div class="col-md-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                                <i class="fas fa-users fa-2x text-primary"></i>
                            </div>
                            <div>
                                <h3 class="mb-0"><?= $playerStats['total'] ?></h3>
                                <p class="text-muted mb-0">Joueurs</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                                <i class="fas fa-futbol fa-2x text-success"></i>
                            </div>
                            <div>
                                <h3 class="mb-0"><?= $matchStats['total'] ?></h3>
                                <p class="text-muted mb-0">Matchs</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                                <i class="fas fa-trophy fa-2x text-info"></i>
                            </div>
                            <div>
                                <h3 class="mb-0">Division <?= htmlspecialchars($team['division']) ?></h3>
                                <p class="text-muted mb-0">Niveau</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations de l'équipe -->
            <div class="col-md-6">
                <div class="card dashboard-card h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-info-circle me-2"></i>Informations de l'équipe
                        </h5>
                        <div class="team-info">
                            <p><strong>Nom:</strong> <?= htmlspecialchars($team['team_name']) ?></p>
                            <p><strong>Localité:</strong> <?= htmlspecialchars($team['locality']) ?></p>
                            <p><strong>Coach:</strong> <?= htmlspecialchars($team['coach_name']) ?></p>
                            <p><strong>Division:</strong> <?= htmlspecialchars($team['division']) ?></p>
                        </div>
                        <a href="edit_team.php" class="btn btn-primary btn-custom mt-3">
                            <i class="fas fa-edit me-2"></i>Modifier les informations
                        </a>
                    </div>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="col-md-6">
                <div class="card dashboard-card h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-bolt me-2"></i>Actions rapides
                        </h5>
                        <div class="d-grid gap-3">
                            <a href="add_player.php" class="btn btn-outline-primary btn-custom">
                                <i class="fas fa-user-plus me-2"></i>Ajouter un joueur
                            </a>
                            <a href="add_match.php" class="btn btn-outline-success btn-custom">
                                <i class="fas fa-plus-circle me-2"></i>Programmer un match
                            </a>
                            <a href="statistics.php" class="btn btn-outline-info btn-custom">
                                <i class="fas fa-chart-bar me-2"></i>Voir les statistiques
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>