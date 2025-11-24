<?php
session_start();
require_once 'config/db_config.php';
require_once 'include/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$pdo = get_db_connection();
$error = '';
$success = '';

// Récupérer les informations actuelles de l'utilisateur
$user_id = $_SESSION['user_id'];
$user = get_user_info($pdo, $user_id); // Fonction définie dans functions.php
$vehicules = get_user_vehicles($pdo, $user_id); // Fonction définie dans functions.php

// Traitement des mises à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_info'])) {
        // Récupérer les informations actuelles pour les champs non remplis
        $current_user = get_user_info($pdo, $user_id);

        $nom = !empty($_POST['nom']) ? $_POST['nom'] : $current_user['nom'];
        $prenom = !empty($_POST['prenom']) ? $_POST['prenom'] : $current_user['prenom'];
        $date_de_naissance = !empty($_POST['date_de_naissance']) ? $_POST['date_de_naissance'] : $current_user['date_de_naissance'];
        $num_telephone = !empty($_POST['num_telephone']) ? $_POST['num_telephone'] : $current_user['num_telephone'];
        $adresse_mail = !empty($_POST['adresse_mail']) ? $_POST['adresse_mail'] : $current_user['adresse_mail'];
        $password = !empty($_POST['password']) ? $_POST['password'] : null;

        if (update_user_info($pdo, $user_id, $nom, $prenom, $date_de_naissance, $num_telephone, $adresse_mail, $password)) {
            $success = "Vos informations personnelles ont été mises à jour avec succès.";
            $user = get_user_info($pdo, $user_id); // Recharger les informations mises à jour
        } else {
            $error = "Une erreur s'est produite lors de la mise à jour de vos informations.";
        }
    }

    if (isset($_POST['delete_vehicle'])) {
        // Suppression d'un véhicule
        $id_vehicule = $_POST['id_vehicule'] ?? '';
        if (delete_vehicle($pdo, $id_vehicule)) {
            $success = "Le véhicule a été supprimé avec succès.";
            $vehicules = get_user_vehicles($pdo, $user_id); // Recharger la liste des véhicules
        } else {
            $error = "Une erreur s'est produite lors de la suppression du véhicule.";
        }
    }
}

include 'include/header.inc.php';
?>

<div style="max-width: 800px; margin: 50px auto;">
    <h2>Paramètres du compte</h2>

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

    <form method="POST" action="parametres.php">
        <h3>Modifier vos informations personnelles</h3>
        <div style="margin-bottom: 15px;">
            <label for="nom">Nom :</label>
            <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($user['nom'] ?? '') ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label for="prenom">Prénom :</label>
            <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label for="date_de_naissance">Date de naissance :</label>
            <input type="date" id="date_de_naissance" name="date_de_naissance" value="<?= htmlspecialchars($user['date_de_naissance'] ?? '') ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label for="num_telephone">Numéro de téléphone :</label>
            <input type="text" id="num_telephone" name="num_telephone" value="<?= htmlspecialchars($user['num_telephone'] ?? '') ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label for="adresse_mail">Adresse e-mail :</label>
            <input type="adresse_mail" id="adresse_mail" name="adresse_mail" value="<?= htmlspecialchars($user['adresse_mail'] ?? '') ?>" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        <div style="margin-bottom: 15px;">
            <label for="password">Mot de passe :</label>
            <input type="password" id="password" name="password" placeholder="Laissez vide pour ne pas changer" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
        </div>
        <button type="submit" name="update_info" style="padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Mettre à jour</button>
    </form>

    <h3>Supprimer un véhicule</h3>
    <?php if (count($vehicules) > 0): ?>
        <ul>
            <?php foreach ($vehicules as $vehicule): ?>
                <li>
                    <?= htmlspecialchars($vehicule['modele']) ?> 
                    <form method="POST" action="parametres.php" style="display: inline;">
                        <input type="hidden" name="id_vehicule" value="<?= htmlspecialchars($vehicule['id_vehicule']) ?>">
                        <button type="submit" name="delete_vehicle" style="padding: 5px 10px; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;">Supprimer</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Vous n'avez aucun véhicule enregistré.</p>
    <?php endif; ?>
</div>

<?php include 'include/footer.inc.php'; ?>