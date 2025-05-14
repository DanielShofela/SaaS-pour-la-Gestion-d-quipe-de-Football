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
} catch(PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// Traitement du changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'password') {
    try {
        if (empty($_POST['current_password']) || empty($_POST['new_password']) || empty($_POST['confirm_password'])) {
            throw new Exception("Tous les champs sont requis");
        }

        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            throw new Exception("Les nouveaux mots de passe ne correspondent pas");
        }

        // Vérifier l'ancien mot de passe
        $stmt = $conn->prepare("SELECT password FROM teams WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $current = $stmt->fetch();

        if (md5($_POST['current_password']) !== $current['password']) {
            throw new Exception("Le mot de passe actuel est incorrect");
        }

        // Mettre à jour le mot de passe
        $stmt = $conn->prepare("UPDATE teams SET password = ? WHERE id = ?");
        $stmt->execute([md5($_POST['new_password']), $_SESSION['user_id']]);

        $_SESSION['success'] = "Mot de passe modifié avec succès";
        header('Location: settings.php');
        exit();
    } catch(Exception $e) {
        $password_error = $e->getMessage();
    }
}

// Traitement du changement d'email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'email') {
    try {
        if (empty($_POST['new_email']) || empty($_POST['password'])) {
            throw new Exception("Tous les champs sont requis");
        }

        // Vérifier le mot de passe
        $stmt = $conn->prepare("SELECT password FROM teams WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $current = $stmt->fetch();

        if (md5($_POST['password']) !== $current['password']) {
            throw new Exception("Le mot de passe est incorrect");
        }

        // Vérifier si l'email n'est pas déjà utilisé
        $stmt = $conn->prepare("SELECT id FROM teams WHERE email = ? AND id != ?");
        $stmt->execute([$_POST['new_email'], $_SESSION['user_id']]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Cette adresse email est déjà utilisée");
        }

        // Mettre à jour l'email
        $stmt = $conn->prepare("UPDATE teams SET email = ? WHERE id = ?");
        $stmt->execute([$_POST['new_email'], $_SESSION['user_id']]);

        $_SESSION['success'] = "Adresse email modifiée avec succès";
        header('Location: settings.php');
        exit();
    } catch(Exception $e) {
        $email_error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - Football SAAS</title>
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
        <h2 class="mb-4">
            <i class="fas fa-cog me-2"></i>Paramètres
        </h2>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Informations de l'équipe -->
            <div class="col-md-6">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-info-circle me-2"></i>Informations de l'équipe
                        </h5>
                        <div class="team-info mb-3">
                            <p><strong>Nom:</strong> <?= htmlspecialchars($team['team_name']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($team['email']) ?></p>
                            <p><strong>Localité:</strong> <?= htmlspecialchars($team['locality']) ?></p>
                            <p><strong>Division:</strong> <?= htmlspecialchars($team['division']) ?></p>
                        </div>
                        <a href="edit_team.php" class="btn btn-primary btn-custom">
                            <i class="fas fa-edit me-2"></i>Modifier les informations
                        </a>
                    </div>
                </div>
            </div>

            <!-- Changement de mot de passe -->
            <div class="col-md-6">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-lock me-2"></i>Changer le mot de passe
                        </h5>
                        <?php if (isset($password_error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?= $password_error ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="password">
                            <div class="mb-3">
                                <label class="form-label">Mot de passe actuel</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nouveau mot de passe</label>
                                <input type="password" name="new_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirmer le nouveau mot de passe</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-custom">
                                <i class="fas fa-save me-2"></i>Changer le mot de passe
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Changement d'email -->
            <div class="col-md-6">
                <div class="card dashboard-card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">
                            <i class="fas fa-envelope me-2"></i>Changer l'adresse email
                        </h5>
                        <?php if (isset($email_error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?= $email_error ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="email">
                            <div class="mb-3">
                                <label class="form-label">Nouvelle adresse email</label>
                                <input type="email" name="new_email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mot de passe (pour confirmation)</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-custom">
                                <i class="fas fa-save me-2"></i>Changer l'email
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 