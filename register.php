<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Football SAAS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Inscription d'une nouvelle équipe</h3>
                    </div>
                    <div class="card-body">
                        <form action="process_register.php" method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="team_name" class="form-label">Nom de l'équipe</label>
                                <input type="text" class="form-control" id="team_name" name="team_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="locality" class="form-label">Localité</label>
                                <input type="text" class="form-control" id="locality" name="locality" required>
                            </div>
                            <div class="mb-3">
                                <label for="coach_name" class="form-label">Nom et prénom du coach</label>
                                <input type="text" class="form-control" id="coach_name" name="coach_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="division" class="form-label">Division</label>
                                <select class="form-select" id="division" name="division" required>
                                    <option value="">Sélectionnez une division</option>
                                    <option value="1">1ère division</option>
                                    <option value="2">2ème division</option>
                                </select>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">S'inscrire</button>
                            </div>
                        </form>
                        <div class="mt-3 text-center">
                            <p>Déjà inscrit ? <a href="login.php">Connectez-vous ici</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
