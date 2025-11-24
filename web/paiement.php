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

// Récupérer les données transmises depuis `achats.php` via $_SESSION
$reservation = $_SESSION['reservation'] ?? null;

if (!$reservation) {
    die("Données manquantes. Veuillez revenir à la page précédente.");
}

$id_parking = $reservation['id_parking'];
$id_vehicule = $reservation['id_vehicule'];
$type_contrat = $reservation['type_contrat'];
$date_debut = $reservation['date_debut'];
$date_fin = $reservation['date_fin'];
$id_place = $reservation['id_place'];

// Calcul du prix
$date_debut_obj = new DateTime($date_debut);
$date_fin_obj = new DateTime($date_fin);
$interval = $date_debut_obj->diff($date_fin_obj);

if ($type_contrat === 'abonnement') {
    $nb_jours = $interval->days; // Nombre de jours
    $prix_total = $nb_jours * 49.99;
} elseif ($type_contrat === 'ticketHoraire') {
    $nb_minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i; // Nombre total de minutes
    $prix_total = $nb_minutes * 0.5;
} else {
    die("Type de contrat invalide.");
}

// Récupérer les détails de la carte enregistrée (si disponible)
$stmt = $pdo->prepare("SELECT detail_carte FROM client WHERE id_client = ?");
$stmt->execute([$_SESSION['user_id']]);
$cartes_enregistrees = $stmt->fetchColumn(); // Récupère le tableau des cartes enregistrées

// Convertir les cartes enregistrées en tableau PHP ou initialiser un tableau vide si null
$cartes_enregistrees = $cartes_enregistrees ? json_decode($cartes_enregistrees, true) : [];

// Traitement du formulaire de paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero_carte = $_POST['numero_carte'] ?? '';
    $date_expiration = $_POST['date_expiration'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    $utiliser_carte_enregistree = $_POST['utiliser_carte_enregistree'] ?? false;

    // Validation des champs
    if ($utiliser_carte_enregistree && isset($cartes_enregistrees[$utiliser_carte_enregistree])) {
        // Utiliser une carte enregistrée
        $carte_utilisee = $cartes_enregistrees[$utiliser_carte_enregistree];
    } elseif (empty($numero_carte) || !preg_match('/^\d{16}$/', $numero_carte) ||
              empty($date_expiration) || !preg_match('/^\d{2}\/\d{2}$/', $date_expiration) ||
              empty($cvv) || !preg_match('/^\d{3}$/', $cvv)) {
        $error = "Veuillez entrer des informations de carte valides.";
    } else {
        // Ajouter la nouvelle carte au tableau des cartes enregistrées
        $carte_utilisee = [
            'numero_carte' => $numero_carte,
            'date_expiration' => $date_expiration,
            'cvv' => $cvv
        ];

        // Sauvegarder la carte si elle n'existe pas déjà
        if (!is_array($cartes_enregistrees)) {
        $cartes_enregistrees = [];
    }
        if (!in_array($carte_utilisee, $cartes_enregistrees)) {
            $cartes_enregistrees[] = $carte_utilisee;

            // Convertir le tableau PHP en un tableau PostgreSQL
            $cartes_pgsql = '{' . implode(',', array_map(function ($carte) {
                // Créer une chaîne de caractères PostgreSQL pour chaque carte
                return '"' . $carte['numero_carte'] . ',' . $carte['date_expiration'] . ',' . $carte['cvv'] . '"';
            }, $cartes_enregistrees)) . '}';

            // Mettre à jour la colonne detail_carte dans la table client
            $stmt = $pdo->prepare("UPDATE client SET detail_carte = ? WHERE id_client = ?");
            $stmt->execute([$cartes_pgsql, $_SESSION['user_id']]);
        }
    }

    if (!$error) {
        try {
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

            // Générer un nouvel id_paiement
            $id_paiement = generer_id_paiement($pdo);

            // Insérer les détails du paiement dans la table paiement
            $date_paiement = (new DateTime())->format('Y-m-d H:i:s');
            $stmt = $pdo->prepare("
                INSERT INTO paiement (id_paiement, id_contrat, id_client, montant, date_paiement) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$id_paiement, $id_contrat, $_SESSION['user_id'], $prix_total, $date_paiement]);

            // Afficher un message de succès avec les détails
            $success = "
                <div style='background-color: #d4edda; color: #155724; padding: 20px; border-radius: 8px; border: 1px solid #c3e6cb;'>
                    <h2 style='margin-top: 0;'>✅ Paiement effectué avec succès !</h2>
                    <p>Votre contrat a été créé avec succès. Voici les détails :</p>
                    <table style='width: 100%; border-collapse: collapse; margin-top: 15px;'>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Nom du client :</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($client['nom']) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Prénom du client :</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($client['prenom']) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Parking choisi :</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($parking['nom']) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Véhicule :</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($vehicule['modele']) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Type de contrat :</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($type_contrat) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Date de début :</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($date_debut) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Date de fin :</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($date_fin) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Montant payé :</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>" . number_format($prix_total, 2) . " €</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Date du paiement :</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($date_paiement) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>ID du contrat :</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($id_contrat) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px; border: 1px solid #ddd;'><strong>ID du paiement :</strong></td>
                            <td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($id_paiement) . "</td>
                        </tr>
                    </table>
                </div>
            ";

            unset($_SESSION['reservation']); // Nettoyer les données de réservation
        } catch (Exception $e) {
            $error = "Erreur lors du traitement du paiement : " . $e->getMessage();
        }
    }
}

include 'include/header.inc.php';
?>

<div style="max-width: 600px; margin: 50px auto;">
    <h2>Paiement</h2>

    <?php if ($error): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            <?= $success ?>
        </div>
    <?php else: ?>
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <h3>Détails du paiement</h3>
            <p><strong>Type de contrat :</strong> <?= htmlspecialchars($type_contrat) ?></p>
            <p><strong>Date de début :</strong> <?= htmlspecialchars($date_debut) ?></p>
            <p><strong>Date de fin :</strong> <?= htmlspecialchars($date_fin) ?></p>
            <p><strong>Prix total :</strong> <?= number_format($prix_total, 2) ?> €</p>
        </div>

        <form method="POST" action="paiement.php">
            <h3>Informations de paiement</h3>

            <?php if (!empty($cartes_enregistrees)): ?>
                <div style="margin-bottom: 15px;">
                    <label for="utiliser_carte_enregistree">Utiliser une carte enregistrée :</label>
                    <select id="utiliser_carte_enregistree" name="utiliser_carte_enregistree" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">-- Sélectionner une carte --</option>
                        <?php foreach ($cartes_enregistrees as $index => $carte): ?>
                            <option value="<?= $index ?>">**** **** **** <?= substr($carte['numero_carte'], -4) ?> (Expire: <?= $carte['date_expiration'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div style="margin-bottom: 15px;">
                <label for="numero_carte">Numéro de carte :</label>
                <input type="text" id="numero_carte" name="numero_carte" placeholder="1234567812345678" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="date_expiration">Date d'expiration (MM/AA) :</label>
                <input type="text" id="date_expiration" name="date_expiration" placeholder="MM/AA" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="cvv">CVV :</label>
                <input type="text" id="cvv" name="cvv" placeholder="123" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>

            <button type="submit" style="padding: 10px 20px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;">Valider</button>
        </form>
    <?php endif; ?>
</div>

<?php include 'include/footer.inc.php'; ?>