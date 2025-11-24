<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'config/db_config.php';

$pdo = get_db_connection();

// Récupérer les informations du client connecté
$stmt = $pdo->prepare("SELECT nom, prenom, adresse_mail, num_telephone FROM client WHERE id_client = ?");
$stmt->execute([$_SESSION['user_id']]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

include 'include/header.inc.php';
?>

<div style="max-width: 1000px; margin: 0 auto;">
    <h2>Tableau de bord</h2>

    <!-- Section des informations personnelles -->
    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <h3>Informations personnelles</h3>
        <?php if ($client): ?>
            <p><strong>Nom :</strong> <?= htmlspecialchars($client['nom']) ?></p>
            <p><strong>Prénom :</strong> <?= htmlspecialchars($client['prenom']) ?></p>
            <p><strong>Adresse email :</strong> <?= htmlspecialchars($client['adresse_mail']) ?></p>
            <p><strong>Numéro de téléphone :</strong> <?= htmlspecialchars($client['num_telephone']) ?></p>
        <?php else: ?>
            <p>Impossible de récupérer vos informations personnelles.</p>
        <?php endif; ?>
    </div>

    <!-- Code existant -->
    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <h3>Options disponibles</h3>
        <ul style="list-style: none; padding: 0;">
            <li style="margin-bottom: 10px;"><a href="vehicule.php" style="color: #007bff; text-decoration: none; font-weight: bold;">🚗 Gérer mes véhicules</a></li>
            <li style="margin-bottom: 10px;"><a href="achats.php" style="color: #007bff; text-decoration: none; font-weight: bold;">📋 Mes contrats</a></li>
            <li style="margin-bottom: 10px;"><a href="#" style="color: #007bff; text-decoration: none; font-weight: bold;">💳 Mes paiements</a></li>
            <li style="margin-bottom: 10px;"><a href="#" style="color: #007bff; text-decoration: none; font-weight: bold;">⚙️ Paramètres</a></li>
        </ul>
    </div>
</div>

<?php include 'include/footer.inc.php'; ?>