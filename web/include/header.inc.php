<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Déterminer le préfixe pour les chemins (pages dans /auth utilisent ../)
$script = $_SERVER['SCRIPT_NAME'] ?? '';
$root = (strpos($script, '/auth/') !== false) ? '../' : '';
$css_path = $root . 'style.css';
$favicon = $root . 'images/parking.png';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="author" content="Nadjib Yasser Omar"/>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($favicon); ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parking IT</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <header class="presentation-header">
        <div class="navbar">
            <a href="<?php echo $root; ?>index.php">
                <img src="<?php echo $root; ?>images/logo.png" 
                alt="Parking IT" 
                style="height: 100px; object-fit: contain;">
            </a>
            <nav>
                <ul class="nav-links">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="<?php echo $root; ?>dashboard.php">Tableau de bord</a></li>
                        <li><a href="<?php echo $root; ?>vehicule.php">Mes Véhicules</a></li>
                        <li><a href="<?php echo $root; ?>achats.php">Achats</a></li>
                        <li><a href="<?php echo $root; ?>logout.php">Déconnexion</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo $root; ?>auth/login.php">Connexion</a></li>
                        <li><a href="<?php echo $root; ?>auth/register.php">Inscription</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
</body>
</html>