<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="author" content="Nadjib Yasser Omar"/>
    <link rel="icon" type="image/png" href="../images/parking.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parking IT</title>
    <link rel="stylesheet" href="style.css"> <!-- Lien vers le fichier CSS -->
</head>
<body>
    <header>
        <div class="navbar">
            <div class="logo">
<h1 style="text-align: center; font-size: 36px; font-weight: bold; margin: 20px 0;">
    <a href="index.php" style="text-decoration: none; color: #ffffffff;">
        Parking IT
    </a>
</h1>            </div>
            <nav>
                <ul class="nav-links">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="dashboard.php">Tableau de bord</a></li>
                        <li><a href="vehicule.php">Mes Vehicule</a></li>
                         <li><a href="achats.php">Achats</a></li>
                        <li><a href="logout.php">Déconnexion</a></li>
                    <?php else: ?>
                        <li><a href="auth/login.php">Connexion</a></li>
                        <li><a href="auth/register.php">Inscription</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <div class="content">
            <h2>Bienvenue sur Parking IT</h2>
            <p>Gestion de Parking Intelligente</p>
        </div>
    </main>
</body>
</html>