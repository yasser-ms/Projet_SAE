<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'config/db_config.php';
require_once 'include/functions.php';

$pdo = get_db_connection();

// Récupérer les informations du client connecté
$stmt = $pdo->prepare("SELECT nom, prenom, adresse_mail, num_telephone FROM client WHERE id_client = ?");
$stmt->execute([$_SESSION['user_id']]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer l'historique de l'utilisateur
$history = get_user_history($pdo, $_SESSION['user_id']);

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

    <!-- Section de l'historique -->
    <div style="margin-top: 50px;">
        <h3>Historique</h3>

        <?php if (!empty($history) && count($history) > 0): ?>
            <table style="width: 100%; border-collapse: collapse; margin-top: 15px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <thead>
                    <tr style="background-color: #f8f9fa;">
                        <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Contrat</th>
                        <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Véhicule</th>
                        <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Parking</th>
                        <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Type de borne</th>
                        <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Heure scannée</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $entry): ?>
                        <tr>
                            <td style="padding: 12px; border: 1px solid #ddd;"><?= htmlspecialchars($entry['id_contrat']) ?></td>
                            <td style="padding: 12px; border: 1px solid #ddd;"><?= htmlspecialchars($entry['vehicule_modele']) ?></td>
                            <td style="padding: 12px; border: 1px solid #ddd;"><?= htmlspecialchars($entry['parking_nom']) ?></td>
                            <td style="padding: 12px; border: 1px solid #ddd;"><?= $entry['type_de_borne'] === 'entree' ? 'Entrée' : 'Sortie' ?></td>
                            <td style="padding: 12px; border: 1px solid #ddd;"><?= htmlspecialchars($entry['heure_scanne']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color: #6c757d; font-style: italic; margin-top: 15px;">Aucun historique disponible pour le moment.</p>
        <?php endif; ?>
    </div>

    <!-- Section des options disponibles -->
    <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 20px;">
        <h3>Options disponibles</h3>
        <ul style="list-style: none; padding: 0;">
            <li style="margin-bottom: 10px;"><a href="vehicule.php" style="color: #007bff; text-decoration: none; font-weight: bold;">🚗 Gérer mes véhicules</a></li>
            <li style="margin-bottom: 10px;"><a href="achats.php" style="color: #007bff; text-decoration: none; font-weight: bold;">📋 Mes contrats</a></li>
            <li style="margin-bottom: 10px;"><a href="penalite.php" style="color: #007bff; text-decoration: none; font-weight: bold;">💳 Régler mes pénalités</a></li>
            <li style="margin-bottom: 10px;"><a href="parametres.php" style="color: #007bff; text-decoration: none; font-weight: bold;">⚙️ Paramètres</a></li>
        </ul>
    </div>
</div>

<?php include 'include/footer.inc.php'; ?>