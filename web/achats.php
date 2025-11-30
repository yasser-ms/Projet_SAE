<?php
/* achats.php
   Gère le processus d'achat de places de parking et de contrats.
*/
session_start();
require_once 'config/db_config.php';
require_once 'include/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$pdo = get_db_connection();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $id_parking = $_POST['id_parking'] ?? null;
    $id_vehicule = $_POST['id_vehicule'] ?? null;
    $type_contrat = $_POST['type_contrat'] ?? null;
    $date_debut = $_POST['date_debut'] ?? null;
    $duree = $_POST['duree'] ?? null; // Durée en heures ou semaines
    $type_vehicule = get_vehicle_type($pdo, $id_vehicule);

    // Vérifier que toutes les données nécessaires sont présentes
    if ($id_parking && $id_vehicule && $type_contrat && $date_debut && $duree) {
        // Calculer la date de fin en fonction du type de contrat
        if ($type_contrat === 'ticketHoraire') {
            if ($duree < 1 || $duree > 24) {
                $error = "La durée pour un ticket horaire doit être comprise entre 1 et 24 heures.";
            } else {
                $date_fin = calculate_end_date($date_debut, $duree, 'hours');
            }
        } elseif ($type_contrat === 'abonnement') {
            if ($duree < 1 || $duree > 52) {
                $error = "La durée pour un abonnement doit être comprise entre 1 et 52 semaines.";
            } else {
                $date_fin = calculate_end_date($date_debut, $duree, 'weeks');
            }
        } else {
            $error = "Type de contrat invalide.";
        }

        if (empty($error)) {
            // Utiliser la fonction attribuer_place pour trouver une place disponible
            $id_place = attribuer_place($pdo, $id_parking, $type_vehicule);

            if ($id_place) {
                // Mettre à jour la disponibilité de la place (est_dispo = false)
                $stmt = $pdo->prepare("UPDATE place SET est_dispo = false WHERE id_place = ?");
                $stmt->execute([$id_place]);

                // Récupérer le modèle du véhicule pour la réservation
                $stmt = $pdo->prepare("SELECT modele FROM vehicule WHERE id_vehicule = ?");
                $stmt->execute([$id_vehicule]);
                $vehicule = $stmt->fetch(PDO::FETCH_ASSOC);
                $vehicule_modele = $vehicule ? $vehicule['modele'] : '';

                // Récupérer le nom du parking pour la réservation
                $stmt = $pdo->prepare("SELECT nom FROM parking WHERE id_parking = ?");
                $stmt->execute([$id_parking]);
                $parking = $stmt->fetch(PDO::FETCH_ASSOC);
                $parking_nom = $parking ? $parking['nom'] : '';

                // Ajout des informations nécessaires à la session
                $_SESSION['reservation'] = [
                    'id_parking' => $id_parking,
                    'id_vehicule' => $id_vehicule,
                    'type_contrat' => $type_contrat,
                    'date_debut' => $date_debut,
                    'date_fin' => $date_fin,
                    'id_place' => $id_place,
                    'montant_p' => calculate_contract_price($type_contrat, $duree),
                    'vehicule_modele' => $vehicule_modele, // Assurez-vous que cette variable est définie
                    'parking_nom' => $parking_nom // Assurez-vous que cette variable est définie
                ];

                // Rediriger vers la page paiement.php
                header("Location: paiement.php?from=contrat");
                exit();
            } else {
                $error = "Aucune place disponible dans le parking sélectionné.";
            }
        }
    } else {
        $error = "Veuillez remplir tous les champs pour effectuer une réservation.";
    }
}
?>

<?php include 'include/header.inc.php'; ?>

<div style="max-width: 1200px; margin: 50px auto;">
    <h2>Liste des parkings</h2>

    <?php if (!empty($error)): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Affichage des parkings -->
    <?php
    $stmt = $pdo->query("SELECT id_parking, nom, adresse, nbrplace FROM parking");
    $parkings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($parkings as $parking):
    ?>
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
                        <select id="type_contrat" name="type_contrat" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" onchange="toggleDurationInput(this.value, '<?= $parking['id_parking'] ?>')">
                            <option value="ticketHoraire">Ticket Horaire</option>
                            <option value="abonnement">Abonnement</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="id_vehicule">Véhicule :</label>
                        <select id="id_vehicule" name="id_vehicule" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                            <?php
                            $stmt = $pdo->prepare("SELECT id_vehicule, modele FROM vehicule WHERE id_client = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            $vehicules = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($vehicules as $vehicule): ?>
                                <option value="<?= $vehicule['id_vehicule'] ?>"><?= htmlspecialchars($vehicule['modele']) ?> (<?= htmlspecialchars($vehicule['id_vehicule']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="date_debut">Date de début :</label>
                        <input type="datetime-local" id="date_debut" name="date_debut" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;" min="<?= date('Y-m-d\TH:i') ?>">
                    </div>

                    <div style="margin-bottom: 15px;" id="duration-input-<?= $parking['id_parking'] ?>">
                        <!-- Champ pour la durée -->
                        <label for="duree">Durée :</label>
                        <input type="number" id="duree" name="duree" min="1" max="24" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <small>Durée en heures pour un ticket horaire (1-24) ou en semaines pour un abonnement (1-52).</small>
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

    function toggleDurationInput(typeContrat, parkingId) {
        const durationInput = document.querySelector(`#duration-input-${parkingId}`);
        const input = durationInput.querySelector('input');
        if (typeContrat === 'ticketHoraire') {
            input.setAttribute('max', '24');
            input.setAttribute('placeholder', 'Durée en heures (1-24)');
        } else if (typeContrat === 'abonnement') {
            input.setAttribute('max', '52');
            input.setAttribute('placeholder', 'Durée en semaines (1-52)');
        }
    }
</script>


<?php include 'include/footer.inc.php'; ?>