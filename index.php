<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Football SAAS - Gestion d'équipe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero {
            position: relative;
            min-height: 500px;
            color: white;
            overflow: hidden;
        }
        .hero-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            background-size: cover;
            background-position: center;
        }
        .hero-slide.active {
            opacity: 1;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            background: rgba(0, 0, 0, 0.6);
            min-height: 500px;
        }
        .feature-card {
            transition: transform 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Football SAAS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Connexion</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary" href="register.php">S'inscrire</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section avec Carrousel -->
    <section class="hero">
        <div id="heroSlides">
            <div class="hero-slide" style="background-image: url('assets/images/photo-1486286701208-1d58e9338013.jpg');"></div>
            <div class="hero-slide" style="background-image: url('assets/images/photo-1517927033932-b3d18e61fb3a.jpg');"></div>
            <div class="hero-slide" style="background-image: url('assets/images/photo-1526232636376-53d03f24f092.jpg');"></div>
        </div>
        <div class="hero-content d-flex align-items-center">
            <div class="container text-center">
                <h1 class="display-4 mb-4">Gérez votre équipe de football efficacement</h1>
                <p class="lead mb-4">Une solution simple et abordable pour les équipes à petit budget</p>
                <a href="register.php" class="btn btn-primary btn-lg">Commencer gratuitement</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Fonctionnalités principales</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x mb-3 text-primary"></i>
                            <h3 class="card-title">Gestion des joueurs</h3>
                            <p class="card-text">Enregistrez et gérez facilement les informations de vos joueurs</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-user-tie fa-3x mb-3 text-primary"></i>
                            <h3 class="card-title">Gestion des coachs</h3>
                            <p class="card-text">Suivez l'historique et les changements de vos entraîneurs</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-file-export fa-3x mb-3 text-primary"></i>
                            <h3 class="card-title">Export des données</h3>
                            <p class="card-text">Exportez vos données facilement au format SQL</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Football SAAS. Tous droits réservés.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script>
        // Carrousel automatique
        document.addEventListener('DOMContentLoaded', function() {
            const slides = document.querySelectorAll('.hero-slide');
            let currentSlide = 0;

            // Afficher la première slide
            slides[0].classList.add('active');

            // Fonction pour changer de slide
            function nextSlide() {
                slides[currentSlide].classList.remove('active');
                currentSlide = (currentSlide + 1) % slides.length;
                slides[currentSlide].classList.add('active');
            }

            // Changer de slide toutes les 5 secondes
            setInterval(nextSlide, 5000);
        });
    </script>
</body>
</html>
