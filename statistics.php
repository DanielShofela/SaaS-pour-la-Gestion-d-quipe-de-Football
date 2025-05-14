<<<<<<< HEAD
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

    // Statistiques des joueurs
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_players,
            SUM(CASE WHEN position = 'Gardien' THEN 1 ELSE 0 END) as goalkeepers,
            SUM(CASE WHEN position = 'Défenseur' THEN 1 ELSE 0 END) as defenders,
            SUM(CASE WHEN position = 'Milieu' THEN 1 ELSE 0 END) as midfielders,
            SUM(CASE WHEN position = 'Attaquant' THEN 1 ELSE 0 END) as forwards,
            ROUND(AVG(TIMESTAMPDIFF(YEAR, birth_date, CURDATE())), 1) as avg_age,
            MIN(TIMESTAMPDIFF(YEAR, birth_date, CURDATE())) as youngest_player,
            MAX(TIMESTAMPDIFF(YEAR, birth_date, CURDATE())) as oldest_player
        FROM players 
        WHERE team_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $player_stats = $stmt->fetch();

    // Statistiques des matchs
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_matches,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as played_matches,
            SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as upcoming_matches,
            SUM(CASE 
                WHEN status = 'completed' AND (
                    (home_team_id = ? AND home_score > away_score) OR 
                    (away_team_id = ? AND away_score > home_score)
                ) THEN 1 
                ELSE 0 
            END) as victories,
            SUM(CASE 
                WHEN status = 'completed' AND (
                    (home_team_id = ? AND home_score < away_score) OR 
                    (away_team_id = ? AND away_score < home_score)
                ) THEN 1 
                ELSE 0 
            END) as defeats,
            SUM(CASE 
                WHEN status = 'completed' AND 
                    ((home_team_id = ? OR away_team_id = ?) AND home_score = away_score)
                THEN 1 
                ELSE 0 
            END) as draws,
            SUM(CASE 
                WHEN home_team_id = ? THEN home_score 
                WHEN away_team_id = ? THEN away_score 
                ELSE 0 
            END) as goals_for,
            SUM(CASE 
                WHEN home_team_id = ? THEN away_score 
                WHEN away_team_id = ? THEN home_score 
                ELSE 0 
            END) as goals_against
        FROM matches 
        WHERE home_team_id = ? OR away_team_id = ?
    ");
    $stmt->execute([
        $_SESSION['user_id'], $_SESSION['user_id'],  // Pour les victoires
        $_SESSION['user_id'], $_SESSION['user_id'],  // Pour les défaites
        $_SESSION['user_id'], $_SESSION['user_id'],  // Pour les nuls
        $_SESSION['user_id'], $_SESSION['user_id'],  // Pour les buts marqués
        $_SESSION['user_id'], $_SESSION['user_id'],  // Pour les buts encaissés
        $_SESSION['user_id'], $_SESSION['user_id']   // Pour la clause WHERE
    ]);
    $match_stats = $stmt->fetch();

    // Calculer les statistiques supplémentaires
    $match_stats['win_rate'] = $match_stats['played_matches'] > 0 
        ? round(($match_stats['victories'] / $match_stats['played_matches']) * 100) 
        : 0;
    
    $match_stats['avg_goals_for'] = $match_stats['played_matches'] > 0 
        ? round($match_stats['goals_for'] / $match_stats['played_matches'], 2) 
        : 0;
    
    $match_stats['avg_goals_against'] = $match_stats['played_matches'] > 0 
        ? round($match_stats['goals_against'] / $match_stats['played_matches'], 2) 
        : 0;

} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques - Football SAAS</title>
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
            <a href="statistics.php" class="nav-link active">
                <i class="fas fa-chart-bar me-2"></i>Statistiques
            </a>
            <a href="settings.php" class="nav-link">
                <i class="fas fa-cog me-2"></i>Paramètres
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-chart-bar me-2"></i>Statistiques de l'équipe
            </h2>
            <a href="dashboard.php" class="btn btn-outline-secondary btn-custom">
                <i class="fas fa-arrow-left me-2"></i>Retour au tableau de bord
            </a>
        </div>

        <!-- Vue d'ensemble -->
        <div class="row g-4">
            <!-- Statistiques des joueurs -->
            <div class="col-md-6">
                <div class="card dashboard-card h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-users me-2"></i>Effectif
                        </h5>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="mb-0"><?= $player_stats['total_players'] ?></h3>
                                    <p class="text-muted mb-0">Joueurs total</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="mb-0"><?= number_format($player_stats['avg_age'], 1) ?> ans</h3>
                                    <p class="text-muted mb-0">Âge moyen</p>
                                    <small class="text-muted">
                                        <?= $player_stats['youngest_player'] ?> - <?= $player_stats['oldest_player'] ?> ans
                                    </small>
                                </div>
                            </div>
                            <div class="col-12">
                                <h6 class="mb-3">Répartition par poste</h6>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Gardiens</span>
                                        <span class="badge bg-primary"><?= $player_stats['goalkeepers'] ?></span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-primary" style="width: <?= ($player_stats['goalkeepers'] / $player_stats['total_players']) * 100 ?>%"></div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Défenseurs</span>
                                        <span class="badge bg-success"><?= $player_stats['defenders'] ?></span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-success" style="width: <?= ($player_stats['defenders'] / $player_stats['total_players']) * 100 ?>%"></div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Milieux</span>
                                        <span class="badge bg-info"><?= $player_stats['midfielders'] ?></span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-info" style="width: <?= ($player_stats['midfielders'] / $player_stats['total_players']) * 100 ?>%"></div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Attaquants</span>
                                        <span class="badge bg-danger"><?= $player_stats['forwards'] ?></span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-danger" style="width: <?= ($player_stats['forwards'] / $player_stats['total_players']) * 100 ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques des matchs -->
            <div class="col-md-6">
                <div class="card dashboard-card h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-futbol me-2"></i>Performances
                        </h5>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="mb-0"><?= $match_stats['win_rate'] ?>%</h3>
                                    <p class="text-muted mb-0">Taux de victoire</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="mb-0"><?= $match_stats['played_matches'] ?></h3>
                                    <p class="text-muted mb-0">Matchs joués</p>
                                </div>
                            </div>
                            <div class="col-12">
                                <h6 class="mb-3">Résultats</h6>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Victoires</span>
                                        <span class="badge bg-success"><?= $match_stats['victories'] ?></span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-success" style="width: <?= ($match_stats['victories'] / $match_stats['played_matches']) * 100 ?>%"></div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Nuls</span>
                                        <span class="badge bg-warning"><?= $match_stats['draws'] ?></span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-warning" style="width: <?= ($match_stats['draws'] / $match_stats['played_matches']) * 100 ?>%"></div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Défaites</span>
                                        <span class="badge bg-danger"><?= $match_stats['defeats'] ?></span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-danger" style="width: <?= ($match_stats['defeats'] / $match_stats['played_matches']) * 100 ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques des buts -->
            <div class="col-md-6">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-chart-line me-2"></i>Statistiques des buts
                        </h5>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded text-center">
                                    <h3 class="mb-0"><?= $match_stats['goals_for'] ?></h3>
                                    <p class="text-muted mb-0">Buts marqués</p>
                                    <small class="text-success">
                                        <?= $match_stats['avg_goals_for'] ?> buts/match
                                    </small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded text-center">
                                    <h3 class="mb-0"><?= $match_stats['goals_against'] ?></h3>
                                    <p class="text-muted mb-0">Buts encaissés</p>
                                    <small class="text-danger">
                                        <?= $match_stats['avg_goals_against'] ?> buts/match
                                    </small>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="p-3 bg-light rounded text-center">
                                    <h3 class="mb-0"><?= $match_stats['goals_for'] - $match_stats['goals_against'] ?></h3>
                                    <p class="text-muted mb-0">Différence de buts</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Prochains matchs -->
            <div class="col-md-6">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-calendar me-2"></i>Aperçu
                        </h5>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded text-center">
                                    <h3 class="mb-0"><?= $match_stats['total_matches'] ?></h3>
                                    <p class="text-muted mb-0">Total matchs</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded text-center">
                                    <h3 class="mb-0"><?= $match_stats['upcoming_matches'] ?></h3>
                                    <p class="text-muted mb-0">Matchs à venir</p>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="p-3 bg-light rounded text-center">
                                    <h3 class="mb-0"><?= $player_stats['total_players'] ?></h3>
                                    <p class="text-muted mb-0">Joueurs disponibles</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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

try {
    // Récupérer les informations de l'équipe
    $stmt = $conn->prepare("SELECT * FROM teams WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $team = $stmt->fetch();

    // Statistiques des joueurs
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_players,
            SUM(CASE WHEN position = 'Gardien' THEN 1 ELSE 0 END) as goalkeepers,
            SUM(CASE WHEN position = 'Défenseur' THEN 1 ELSE 0 END) as defenders,
            SUM(CASE WHEN position = 'Milieu' THEN 1 ELSE 0 END) as midfielders,
            SUM(CASE WHEN position = 'Attaquant' THEN 1 ELSE 0 END) as forwards,
            ROUND(AVG(TIMESTAMPDIFF(YEAR, birth_date, CURDATE())), 1) as avg_age,
            MIN(TIMESTAMPDIFF(YEAR, birth_date, CURDATE())) as youngest_player,
            MAX(TIMESTAMPDIFF(YEAR, birth_date, CURDATE())) as oldest_player
        FROM players 
        WHERE team_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $player_stats = $stmt->fetch();

    // Statistiques des matchs
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_matches,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as played_matches,
            SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as upcoming_matches,
            SUM(CASE 
                WHEN status = 'completed' AND (
                    (home_team_id = ? AND home_score > away_score) OR 
                    (away_team_id = ? AND away_score > home_score)
                ) THEN 1 
                ELSE 0 
            END) as victories,
            SUM(CASE 
                WHEN status = 'completed' AND (
                    (home_team_id = ? AND home_score < away_score) OR 
                    (away_team_id = ? AND away_score < home_score)
                ) THEN 1 
                ELSE 0 
            END) as defeats,
            SUM(CASE 
                WHEN status = 'completed' AND 
                    ((home_team_id = ? OR away_team_id = ?) AND home_score = away_score)
                THEN 1 
                ELSE 0 
            END) as draws,
            SUM(CASE 
                WHEN home_team_id = ? THEN home_score 
                WHEN away_team_id = ? THEN away_score 
                ELSE 0 
            END) as goals_for,
            SUM(CASE 
                WHEN home_team_id = ? THEN away_score 
                WHEN away_team_id = ? THEN home_score 
                ELSE 0 
            END) as goals_against
        FROM matches 
        WHERE home_team_id = ? OR away_team_id = ?
    ");
    $stmt->execute([
        $_SESSION['user_id'], $_SESSION['user_id'],  // Pour les victoires
        $_SESSION['user_id'], $_SESSION['user_id'],  // Pour les défaites
        $_SESSION['user_id'], $_SESSION['user_id'],  // Pour les nuls
        $_SESSION['user_id'], $_SESSION['user_id'],  // Pour les buts marqués
        $_SESSION['user_id'], $_SESSION['user_id'],  // Pour les buts encaissés
        $_SESSION['user_id'], $_SESSION['user_id']   // Pour la clause WHERE
    ]);
    $match_stats = $stmt->fetch();

    // Calculer les statistiques supplémentaires
    $match_stats['win_rate'] = $match_stats['played_matches'] > 0 
        ? round(($match_stats['victories'] / $match_stats['played_matches']) * 100) 
        : 0;
    
    $match_stats['avg_goals_for'] = $match_stats['played_matches'] > 0 
        ? round($match_stats['goals_for'] / $match_stats['played_matches'], 2) 
        : 0;
    
    $match_stats['avg_goals_against'] = $match_stats['played_matches'] > 0 
        ? round($match_stats['goals_against'] / $match_stats['played_matches'], 2) 
        : 0;

} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques - Football SAAS</title>
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
            <a href="statistics.php" class="nav-link active">
                <i class="fas fa-chart-bar me-2"></i>Statistiques
            </a>
            <a href="settings.php" class="nav-link">
                <i class="fas fa-cog me-2"></i>Paramètres
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-chart-bar me-2"></i>Statistiques de l'équipe
            </h2>
            <a href="dashboard.php" class="btn btn-outline-secondary btn-custom">
                <i class="fas fa-arrow-left me-2"></i>Retour au tableau de bord
            </a>
        </div>

        <!-- Vue d'ensemble -->
        <div class="row g-4">
            <!-- Statistiques des joueurs -->
            <div class="col-md-6">
                <div class="card dashboard-card h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-users me-2"></i>Effectif
                        </h5>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="mb-0"><?= $player_stats['total_players'] ?></h3>
                                    <p class="text-muted mb-0">Joueurs total</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="mb-0"><?= number_format($player_stats['avg_age'], 1) ?> ans</h3>
                                    <p class="text-muted mb-0">Âge moyen</p>
                                    <small class="text-muted">
                                        <?= $player_stats['youngest_player'] ?> - <?= $player_stats['oldest_player'] ?> ans
                                    </small>
                                </div>
                            </div>
                            <div class="col-12">
                                <h6 class="mb-3">Répartition par poste</h6>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Gardiens</span>
                                        <span class="badge bg-primary"><?= $player_stats['goalkeepers'] ?></span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-primary" style="width: <?= ($player_stats['goalkeepers'] / $player_stats['total_players']) * 100 ?>%"></div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Défenseurs</span>
                                        <span class="badge bg-success"><?= $player_stats['defenders'] ?></span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-success" style="width: <?= ($player_stats['defenders'] / $player_stats['total_players']) * 100 ?>%"></div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Milieux</span>
                                        <span class="badge bg-info"><?= $player_stats['midfielders'] ?></span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-info" style="width: <?= ($player_stats['midfielders'] / $player_stats['total_players']) * 100 ?>%"></div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Attaquants</span>
                                        <span class="badge bg-danger"><?= $player_stats['forwards'] ?></span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-danger" style="width: <?= ($player_stats['forwards'] / $player_stats['total_players']) * 100 ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques des matchs -->
            <div class="col-md-6">
                <div class="card dashboard-card h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-futbol me-2"></i>Performances
                        </h5>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="mb-0"><?= $match_stats['win_rate'] ?>%</h3>
                                    <p class="text-muted mb-0">Taux de victoire</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="mb-0"><?= $match_stats['played_matches'] ?></h3>
                                    <p class="text-muted mb-0">Matchs joués</p>
                                </div>
                            </div>
                            <div class="col-12">
                                <h6 class="mb-3">Résultats</h6>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Victoires</span>
                                        <span class="badge bg-success"><?= $match_stats['victories'] ?></span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-success" style="width: <?= ($match_stats['victories'] / $match_stats['played_matches']) * 100 ?>%"></div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Nuls</span>
                                        <span class="badge bg-warning"><?= $match_stats['draws'] ?></span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-warning" style="width: <?= ($match_stats['draws'] / $match_stats['played_matches']) * 100 ?>%"></div>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Défaites</span>
                                        <span class="badge bg-danger"><?= $match_stats['defeats'] ?></span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar bg-danger" style="width: <?= ($match_stats['defeats'] / $match_stats['played_matches']) * 100 ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques des buts -->
            <div class="col-md-6">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-chart-line me-2"></i>Statistiques des buts
                        </h5>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded text-center">
                                    <h3 class="mb-0"><?= $match_stats['goals_for'] ?></h3>
                                    <p class="text-muted mb-0">Buts marqués</p>
                                    <small class="text-success">
                                        <?= $match_stats['avg_goals_for'] ?> buts/match
                                    </small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded text-center">
                                    <h3 class="mb-0"><?= $match_stats['goals_against'] ?></h3>
                                    <p class="text-muted mb-0">Buts encaissés</p>
                                    <small class="text-danger">
                                        <?= $match_stats['avg_goals_against'] ?> buts/match
                                    </small>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="p-3 bg-light rounded text-center">
                                    <h3 class="mb-0"><?= $match_stats['goals_for'] - $match_stats['goals_against'] ?></h3>
                                    <p class="text-muted mb-0">Différence de buts</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Prochains matchs -->
            <div class="col-md-6">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-calendar me-2"></i>Aperçu
                        </h5>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded text-center">
                                    <h3 class="mb-0"><?= $match_stats['total_matches'] ?></h3>
                                    <p class="text-muted mb-0">Total matchs</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded text-center">
                                    <h3 class="mb-0"><?= $match_stats['upcoming_matches'] ?></h3>
                                    <p class="text-muted mb-0">Matchs à venir</p>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="p-3 bg-light rounded text-center">
                                    <h3 class="mb-0"><?= $player_stats['total_players'] ?></h3>
                                    <p class="text-muted mb-0">Joueurs disponibles</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
>>>>>>> a9acff600a16e9503acb7cdb1744fd80ae22878d
</html> 