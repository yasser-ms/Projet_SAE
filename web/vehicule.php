<?php
session_start();
require_once 'config/db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$pdo = get_db_connection();
$error = '';
$success = '';

// Traitement du formulaire pour ajouter un nouveau véhicule
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_vehicule = trim($_POST['id_vehicule'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $modele = trim($_POST['modele'] ?? '');

    // Validation des champs
    if (empty($id_vehicule) || empty($type) || empty($modele)) {
        $error = "Veuillez remplir tous les champs.";
    } elseif (!preg_match('/^[A-Z]{2}-\d{3}-[A-Z]{2}$/', $id_vehicule)) {
        $error = "Le matricule doit être au format AA-000-BB.";
    } else {
        try {
            // Vérifier si le véhicule existe déjà
            $stmt = $pdo->prepare("SELECT id_vehicule FROM vehicule WHERE id_vehicule = ?");
            $stmt->execute([$id_vehicule]);
            if ($stmt->fetch()) {
                $error = "Ce véhicule est déjà enregistré.";
            } else {
                // Insérer le nouveau véhicule
                $stmt = $pdo->prepare("INSERT INTO vehicule (id_vehicule, id_client, type, modele) VALUES (?, ?, ?, ?)");
                $stmt->execute([$id_vehicule, $_SESSION['user_id'], $type, $modele]);
                $success = "Véhicule ajouté avec succès.";
            }
        } catch (Exception $e) {
            $error = "Erreur lors de l'ajout du véhicule : " . $e->getMessage();
        }
    }
}

// Récupérer les véhicules de l'utilisateur connecté
$stmt = $pdo->prepare("SELECT id_vehicule, type, modele FROM vehicule WHERE id_client = ?");
$stmt->execute([$_SESSION['user_id']]);
$vehicules = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'include/header.inc.php';
?>

<div style="max-width: 800px; margin: 50px auto;">
    <h2>Mes véhicules</h2>

    <?php if ($error): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <!-- Liste des véhicules -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
        <thead>
            <tr>
                <th style="border: 1px solid #ddd; padding: 8px;">Matricule</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Type</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Modèle</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vehicules as $vehicule): ?>
                <tr>
                    <td style="border: 1px solid #ddd; padding: 8px;"><?= htmlspecialchars($vehicule['id_vehicule']) ?></td>
                    <td style="border: 1px solid #ddd; padding: 8px;"><?= htmlspecialchars($vehicule['type']) ?></td>
                    <td style="border: 1px solid #ddd; padding: 8px;"><?= htmlspecialchars($vehicule['modele']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Formulaire pour ajouter un véhicule -->
    <h3>Ajouter un véhicule</h3>
    <form method="POST" action="vehicule.php">
        <div style="margin-bottom: 15px;">
            <label for="id_vehicule">Matricule :</label>
            <input type="text" id="id_vehicule" name="id_vehicule" placeholder="AA-000-BB" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label for="type">Type :</label>
            <select id="type" name="type" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="voiture">Voiture</option>
                <option value="camion">Camion</option>
                <option value="moto">Moto</option>
                <option value="bus">Bus</option>
            </select>
        </div>
        <div style="margin-bottom: 15px;">
            <label for="modele">Modèle :</label>
            <input type="text" id="modele" name="modele" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        <button type="submit" style="padding: 10px 20px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;">Ajouter</button>
    </form>
</div>

<?php include 'include/footer.inc.php'; ?>