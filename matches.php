<<<<<<< HEAD
<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

try {
    // Mettre à jour automatiquement le statut des matchs passés
    $stmt = $conn->prepare("
        UPDATE matches 
        SET status = 'completed' 
        WHERE match_date < NOW() 
        AND status = 'scheduled'
    ");
    $stmt->execute();

    // Récupérer les informations de l'équipe
    $stmt = $conn->prepare("SELECT * FROM teams WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $team = $stmt->fetch();

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
            END) as victories
        FROM matches 
        WHERE home_team_id = ? OR away_team_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $stats = $stmt->fetch();

    // Récupérer tous les matchs
    $stmt = $conn->prepare("
        SELECT m.*, 
            home.team_name as home_team_name
        FROM matches m
        LEFT JOIN teams home ON m.home_team_id = home.id
        WHERE m.home_team_id = ? OR m.away_team_id = ?
        ORDER BY m.match_date DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $matches = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// Définir les couleurs par statut
$status_colors = [
    'scheduled' => 'info',
    'in_progress' => 'warning',
    'completed' => 'success'
];

// Traduire les statuts
$status_labels = [
    'scheduled' => 'Programmé',
    'in_progress' => 'En cours',
    'completed' => 'Terminé'
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matchs - Football SAAS</title>
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
        <!-- En-tête -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Gestion des Matchs</h2>
                <p class="text-muted mb-0">
                    <?= $stats['total_matches'] ?> matchs • 
                    <?= $stats['upcoming_matches'] ?> à venir • 
                    <?= $stats['victories'] ?> victoires
                </p>
            </div>
            <a href="add_match.php" class="btn btn-primary btn-custom">
                <i class="fas fa-plus me-2"></i>Programmer un match
            </a>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Liste des matchs -->
        <div class="card dashboard-card">
            <div class="card-body">
                <?php if (count($matches) > 0): ?>
                    <?php foreach ($matches as $match): ?>
                        <div class="match-item">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <div class="match-date">
                                        <div class="mb-1">
                                            <i class="far fa-calendar me-2"></i>
                                            <?= date('d/m/Y', strtotime($match['match_date'])) ?>
                                        </div>
                                        <div>
                                            <i class="far fa-clock me-2"></i>
                                            <?= date('H:i', strtotime($match['match_date'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="match-teams text-center">
                                        <?php if ($match['home_team_id'] == $_SESSION['user_id']): ?>
                                            <span class="team-name"><?= htmlspecialchars($team['team_name']) ?></span>
                                            <div class="match-score">
                                                <?php if ($match['home_score'] !== null && $match['away_score'] !== null): ?>
                                                    <span class="score"><?= $match['home_score'] ?> - <?= $match['away_score'] ?></span>
                                                <?php else: ?>
                                                    <span class="vs">VS</span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="team-name">
                                                <?= htmlspecialchars($match['away_team_name'] ?? 'Équipe non définie') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="team-name">
                                                <?= htmlspecialchars($match['away_team_name'] ?? 'Équipe non définie') ?>
                                            </span>
                                            <div class="match-score">
                                                <?php if ($match['home_score'] !== null && $match['away_score'] !== null): ?>
                                                    <span class="score"><?= $match['away_score'] ?> - <?= $match['home_score'] ?></span>
                                                <?php else: ?>
                                                    <span class="vs">VS</span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="team-name"><?= htmlspecialchars($team['team_name']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="match-status text-end">
                                        <span class="badge bg-<?= $status_colors[$match['status']] ?> mb-2">
                                            <?= $status_labels[$match['status']] ?>
                                        </span>
                                        <div class="match-actions">
                                            <a href="edit_match.php?id=<?= $match['id'] ?>" 
                                               class="btn btn-outline-primary btn-sm btn-custom me-2">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_match.php?id=<?= $match['id'] ?>" 
                                               class="btn btn-outline-danger btn-sm btn-custom"
                                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce match ?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-futbol fa-3x text-muted mb-3"></i>
                        <h5>Aucun match programmé</h5>
                        <p class="text-muted">Commencez par ajouter des matchs à votre calendrier</p>
                        <a href="add_match.php" class="btn btn-primary btn-custom">
                            <i class="fas fa-plus me-2"></i>Programmer un match
                        </a>
                    </div>
                <?php endif; ?>
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
    // Mettre à jour automatiquement le statut des matchs passés
    $stmt = $conn->prepare("
        UPDATE matches 
        SET status = 'completed' 
        WHERE match_date < NOW() 
        AND status = 'scheduled'
    ");
    $stmt->execute();

    // Récupérer les informations de l'équipe
    $stmt = $conn->prepare("SELECT * FROM teams WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $team = $stmt->fetch();

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
            END) as victories
        FROM matches 
        WHERE home_team_id = ? OR away_team_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $stats = $stmt->fetch();

    // Récupérer tous les matchs
    $stmt = $conn->prepare("
        SELECT m.*, 
            home.team_name as home_team_name
        FROM matches m
        LEFT JOIN teams home ON m.home_team_id = home.id
        WHERE m.home_team_id = ? OR m.away_team_id = ?
        ORDER BY m.match_date DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $matches = $stmt->fetchAll();
} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// Définir les couleurs par statut
$status_colors = [
    'scheduled' => 'info',
    'in_progress' => 'warning',
    'completed' => 'success'
];

// Traduire les statuts
$status_labels = [
    'scheduled' => 'Programmé',
    'in_progress' => 'En cours',
    'completed' => 'Terminé'
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matchs - Football SAAS</title>
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
        <!-- En-tête -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Gestion des Matchs</h2>
                <p class="text-muted mb-0">
                    <?= $stats['total_matches'] ?> matchs • 
                    <?= $stats['upcoming_matches'] ?> à venir • 
                    <?= $stats['victories'] ?> victoires
                </p>
            </div>
            <a href="add_match.php" class="btn btn-primary btn-custom">
                <i class="fas fa-plus me-2"></i>Programmer un match
            </a>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Liste des matchs -->
        <div class="card dashboard-card">
            <div class="card-body">
                <?php if (count($matches) > 0): ?>
                    <?php foreach ($matches as $match): ?>
                        <div class="match-item">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <div class="match-date">
                                        <div class="mb-1">
                                            <i class="far fa-calendar me-2"></i>
                                            <?= date('d/m/Y', strtotime($match['match_date'])) ?>
                                        </div>
                                        <div>
                                            <i class="far fa-clock me-2"></i>
                                            <?= date('H:i', strtotime($match['match_date'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="match-teams text-center">
                                        <?php if ($match['home_team_id'] == $_SESSION['user_id']): ?>
                                            <span class="team-name"><?= htmlspecialchars($team['team_name']) ?></span>
                                            <div class="match-score">
                                                <?php if ($match['home_score'] !== null && $match['away_score'] !== null): ?>
                                                    <span class="score"><?= $match['home_score'] ?> - <?= $match['away_score'] ?></span>
                                                <?php else: ?>
                                                    <span class="vs">VS</span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="team-name">
                                                <?= htmlspecialchars($match['away_team_name'] ?? 'Équipe non définie') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="team-name">
                                                <?= htmlspecialchars($match['away_team_name'] ?? 'Équipe non définie') ?>
                                            </span>
                                            <div class="match-score">
                                                <?php if ($match['home_score'] !== null && $match['away_score'] !== null): ?>
                                                    <span class="score"><?= $match['away_score'] ?> - <?= $match['home_score'] ?></span>
                                                <?php else: ?>
                                                    <span class="vs">VS</span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="team-name"><?= htmlspecialchars($team['team_name']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="match-status text-end">
                                        <span class="badge bg-<?= $status_colors[$match['status']] ?> mb-2">
                                            <?= $status_labels[$match['status']] ?>
                                        </span>
                                        <div class="match-actions">
                                            <a href="edit_match.php?id=<?= $match['id'] ?>" 
                                               class="btn btn-outline-primary btn-sm btn-custom me-2">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_match.php?id=<?= $match['id'] ?>" 
                                               class="btn btn-outline-danger btn-sm btn-custom"
                                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce match ?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-futbol fa-3x text-muted mb-3"></i>
                        <h5>Aucun match programmé</h5>
                        <p class="text-muted">Commencez par ajouter des matchs à votre calendrier</p>
                        <a href="add_match.php" class="btn btn-primary btn-custom">
                            <i class="fas fa-plus me-2"></i>Programmer un match
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
>>>>>>> a9acff600a16e9503acb7cdb1744fd80ae22878d
</html> 