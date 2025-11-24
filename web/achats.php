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

// Traitement du formulaire pour créer un contrat
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_parking = $_POST['id_parking'] ?? '';
    $id_vehicule = $_POST['id_vehicule'] ?? '';
    $type_contrat = $_POST['type_contrat'] ?? '';
    $date_debut = $_POST['date_debut'] ?? '';
    $date_fin = $_POST['date_fin'] ?? '';

    // Validation des champs
    if (empty($id_parking) || empty($id_vehicule) || empty($type_contrat) || empty($date_debut) || empty($date_fin)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        try {
            // Chercher une place disponible dans le parking choisi
            $stmt = $pdo->prepare("
                SELECT id_place 
                FROM place 
                WHERE id_parking = ? AND est_dispo = true 
                LIMIT 1
            ");
            $stmt->execute([$id_parking]);
            $place = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$place) {
                $error = "Aucune place disponible dans ce parking.";
            } else {
                $id_place = $place['id_place'];

                // Générer un nouvel id_contrat
                $id_contrat = generer_id_contrat($pdo);

                // Insérer le contrat dans la table contrat
                $stmt = $pdo->prepare("
                    INSERT INTO contrat (id_contrat, id_vehicule, id_place, date_debut, date_fin, etat_contrat, type_contrat) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$id_contrat, $id_vehicule, $id_place, $date_debut, $date_fin, 'actif', $type_contrat]);

                // Mettre à jour la disponibilité de la place
                $stmt = $pdo->prepare("UPDATE place SET est_dispo = false WHERE id_place = ?");
                $stmt->execute([$id_place]);

                $success = "Contrat créé avec succès. Place attribuée : " . htmlspecialchars($id_place);
            }
        } catch (Exception $e) {
            $error = "Erreur lors de la création du contrat : " . $e->getMessage();
        }
    }
}

// Récupérer les parkings
$stmt = $pdo->query("SELECT id_parking, nom, adresse, nbrplace FROM parking");
$parkings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les véhicules de l'utilisateur connecté
$stmt = $pdo->prepare("SELECT id_vehicule, modele FROM vehicule WHERE id_client = ?");
$stmt->execute([$_SESSION['user_id']]);
$vehicules = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'include/header.inc.php';
?>

<div style="max-width: 1200px; margin: 50px auto;">
    <h2>Liste des parkings</h2>

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

    <!-- Affichage des parkings -->
    <?php foreach ($parkings as $parking): ?>
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <h3><?= htmlspecialchars($parking['nom']) ?></h3>
            <p><strong>Adresse :</strong> <?= htmlspecialchars($parking['adresse']) ?></p>
            <p><strong>Nombre de places :</strong> <?= htmlspecialchars($parking['nbrplace']) ?></p>
            <button onclick="toggleForm('form-<?= $parking['id_parking'] ?>')" style="padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Réserver</button>

            <!-- Formulaire déroulant -->
            <div id="form-<?= $parking['id_parking'] ?>" style="display: none; margin-top: 20px;">
                <form method="POST" action="achats.php">
                    <input type="hidden" name="id_parking" value="<?= $parking['id_parking'] ?>">

                    <div style="margin-bottom: 15px;">
                        <label for="type_contrat">Type de contrat :</label>
                        <select id="type_contrat" name="type_contrat" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="ticketHoraire">Ticket Horaire</option>
                            <option value="abonnement">Abonnement</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="id_vehicule">Véhicule :</label>
                        <select id="id_vehicule" name="id_vehicule" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                            <?php foreach ($vehicules as $vehicule): ?>
                                <option value="<?= $vehicule['id_vehicule'] ?>"><?= htmlspecialchars($vehicule['modele']) ?> (<?= htmlspecialchars($vehicule['id_vehicule']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="date_debut">Date de début :</label>
                        <input type="datetime-local" id="date_debut" name="date_debut" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="date_fin">Date de fin :</label>
                        <input type="datetime-local" id="date_fin" name="date_fin" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>

                    <button type="submit" style="padding: 10px 20px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;">Valider</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    function toggleForm(formId) {
        const form = document.getElementById(formId);
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
</script>

<?php include 'include/footer.inc.php'; ?>