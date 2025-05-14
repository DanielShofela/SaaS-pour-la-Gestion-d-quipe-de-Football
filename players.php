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

    // Récupérer les statistiques des joueurs
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_players,
            SUM(CASE WHEN position = 'Gardien' THEN 1 ELSE 0 END) as goalkeepers,
            SUM(CASE WHEN position = 'Défenseur' THEN 1 ELSE 0 END) as defenders,
            SUM(CASE WHEN position = 'Milieu' THEN 1 ELSE 0 END) as midfielders,
            SUM(CASE WHEN position = 'Attaquant' THEN 1 ELSE 0 END) as forwards,
            ROUND(AVG(TIMESTAMPDIFF(YEAR, birth_date, CURDATE())), 1) as avg_age
        FROM players 
        WHERE team_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $stats = $stmt->fetch();

    // Récupérer la liste des joueurs
    $stmt = $conn->prepare("
        SELECT *, TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) as age 
        FROM players 
        WHERE team_id = ? 
        ORDER BY position, last_name
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $players = $stmt->fetchAll();

} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// Définir les couleurs par position
$position_colors = [
    'Gardien' => 'primary',
    'Défenseur' => 'success',
    'Milieu' => 'info',
    'Attaquant' => 'danger'
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Joueurs - Football SAAS</title>
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
        <!-- En-tête -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Gestion des Joueurs</h2>
                <p class="text-muted mb-0">
                    <?= $stats['total_players'] ?> joueurs • 
                    Âge moyen : <?= number_format($stats['avg_age'], 1) ?> ans
                </p>
            </div>
            <a href="add_player.php" class="btn btn-primary btn-custom">
                <i class="fas fa-user-plus me-2"></i>Ajouter un joueur
            </a>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Statistiques rapides -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                                <i class="fas fa-user-shield fa-2x text-primary"></i>
                            </div>
                            <div>
                                <h3 class="mb-0"><?= $stats['goalkeepers'] ?></h3>
                                <p class="text-muted mb-0">Gardiens</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                                <i class="fas fa-shield-alt fa-2x text-success"></i>
                            </div>
                            <div>
                                <h3 class="mb-0"><?= $stats['defenders'] ?></h3>
                                <p class="text-muted mb-0">Défenseurs</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                                <i class="fas fa-running fa-2x text-info"></i>
                            </div>
                            <div>
                                <h3 class="mb-0"><?= $stats['midfielders'] ?></h3>
                                <p class="text-muted mb-0">Milieux</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3">
                                <i class="fas fa-futbol fa-2x text-danger"></i>
                            </div>
                            <div>
                                <h3 class="mb-0"><?= $stats['forwards'] ?></h3>
                                <p class="text-muted mb-0">Attaquants</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des joueurs -->
        <div class="card dashboard-card">
            <div class="card-body">
                <?php if (count($players) > 0): ?>
                    <?php 
                    $current_position = '';
                    foreach ($players as $player): 
                        if ($current_position != $player['position']):
                            if ($current_position != '') echo '</div>'; // Fermer le groupe précédent
                            $current_position = $player['position'];
                    ?>
                            <h5 class="mt-4 mb-3 text-<?= $position_colors[$player['position']] ?>">
                                <i class="fas fa-users me-2"></i><?= $player['position'] ?>s
                            </h5>
                            <div class="player-group">
                    <?php endif; ?>

                        <div class="player-item">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <?php if (!empty($player['photo_path']) && file_exists($player['photo_path'])): ?>
                                        <img src="<?= htmlspecialchars($player['photo_path']) ?>" 
                                             alt="Photo" class="player-photo">
                                    <?php else: ?>
                                        <div class="player-avatar bg-<?= $position_colors[$player['position']] ?> bg-opacity-25">
                                            <i class="fas fa-user text-<?= $position_colors[$player['position']] ?>"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col">
                                    <h5 class="mb-1">
                                        <?= htmlspecialchars($player['last_name']) ?> 
                                        <?= htmlspecialchars($player['first_name']) ?>
                                        <span class="badge bg-<?= $position_colors[$player['position']] ?> ms-2">
                                            #<?= $player['jersey_number'] ?>
                                        </span>
                                    </h5>
                                    <div class="text-muted">
                                        <i class="fas fa-calendar me-2"></i><?= $player['age'] ?> ans
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-phone me-2"></i><?= htmlspecialchars($player['contact']) ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <a href="edit_player.php?id=<?= $player['id'] ?>" 
                                       class="btn btn-outline-primary btn-sm btn-custom me-2">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete_player.php?id=<?= $player['id'] ?>" 
                                       class="btn btn-outline-danger btn-sm btn-custom"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce joueur ?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div><!-- Fermer le dernier groupe -->
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5>Aucun joueur enregistré</h5>
                        <p class="text-muted">Commencez par ajouter des joueurs à votre équipe</p>
                        <a href="add_player.php" class="btn btn-primary btn-custom">
                            <i class="fas fa-user-plus me-2"></i>Ajouter un joueur
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>