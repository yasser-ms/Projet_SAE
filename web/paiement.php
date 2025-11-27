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

// Récupérer la source (GET en priorité puis POST)
$from = $_GET['from'] ?? $_POST['from'] ?? null;
$is_penalty_payment = ($from === 'penalite');
$is_contract_payment = ($from === 'contrat');

// Récupérer la réservation si présent (contrat)
$reservation = $_SESSION['reservation'] ?? null;

// Si la page est chargée en GET et c'est un paiement de pénalité, lire les params
// (utile pour préremplir les champs cachés dans le formulaire)
$get_id_penalite = $_GET['id_penalite'] ?? null;
$get_montant_p   = $_GET['montant_p'] ?? null;

// Traitement du formulaire de paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nettoyage des saisies
    $numero_carte    = trim($_POST['numero_carte'] ?? '');
    $date_expiration = trim($_POST['date_expiration'] ?? '');
    $cvv             = trim($_POST['cvv'] ?? '');

    // Validation des champs carte
    if (empty($numero_carte) || !preg_match('/^\d{16}$/', $numero_carte) ||
        empty($date_expiration) || !preg_match('/^\d{2}\/\d{2}$/', $date_expiration) ||
        empty($cvv) || !preg_match('/^\d{3}$/', $cvv)) {
        $error = "Veuillez entrer des informations de carte valides (16 chiffres, MM/AA, CVV 3 chiffres).";
    } else {
        try {
            // Enregistrer les détails de la carte dans la table client
            update_card_details($pdo, $_SESSION['user_id'], $numero_carte, $date_expiration, $cvv);

            // --- Cas pénalité ---
            if ($is_penalty_payment) {
                // Priorité POST puis GET (cela permet de renvoyer le formulaire avec erreurs)
                $id_penalite = $_POST['id_penalite'] ?? $_GET['id_penalite'] ?? null;
                $montant_p_raw = $_POST['montant_p'] ?? $_GET['montant_p'] ?? null;

                // Validation du montant : convertir en float sûr
                if ($montant_p_raw !== null && $montant_p_raw !== '') {
                    // remplacer la virgule par un point si l'utilisateur a utilisé une virgule
                    $montant_p_raw = str_replace(',', '.', $montant_p_raw);
                    if (!is_numeric($montant_p_raw)) {
                        $error = "Montant invalide.";
                    } else {
                        $montant_p = (float) $montant_p_raw;
                    }
                } else {
                    $montant_p = null;
                }

                if (!$id_penalite || $montant_p === null) {
                    $error = $error ?: "Données de pénalité manquantes.";
                } else {
                    // Vérifier que la pénalité existe
                    $penalty = is_penalty_payment($pdo, $id_penalite);

                    if (!$penalty) {
                        $error = "La pénalité spécifiée n'existe pas.";
                    } else {
                        // Supprimer la pénalité en transaction (sécurisé)
                        try {
                            $pdo->beginTransaction();
                            $deleted = delete_penalty($pdo, $id_penalite);
                            // delete_penalty doit retourner true/false idéalement. Si non, on se contente de commit.
                            $pdo->commit();

                            // Message de succès
                            $success = "
                                <div style='background-color: #d4edda; color: #155724; padding: 20px; border-radius: 8px; border: 1px solid #c3e6cb;'>
                                    <h2 style='margin-top: 0;'>✅ Paiement de la pénalité effectué avec succès !</h2>
                                    <p><strong>Montant payé :</strong> " . number_format($montant_p, 2, ',', ' ') . " €</p>
                                    <p><strong>Description :</strong> " . htmlspecialchars($penalty['description']) . "</p>
                                    <p><strong>Date de création :</strong> " . htmlspecialchars($penalty['date_creation']) . "</p>
                                </div>
                                 <div style='text-align: center; margin-top: 20px;'>
                                <a href='dashboard.php' style='padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; font-size: 16px;'>Retour au tableau de bord</a>
                            </div>
                            ";
                        } catch (Exception $e) {
                            // Annuler si erreur
                            if ($pdo->inTransaction()) {
                                $pdo->rollBack();
                            }
                            // Erreur technique — ne pas exposer toute la stack à l'utilisateur
                            $error = "Erreur lors de la suppression de la pénalité.";
                            // Optionnel : logger l'erreur pour debug (remplace par ton logger)
                            error_log("Erreur suppression pénalité id=$id_penalite : " . $e->getMessage());
                        }
                    }
                }

            // --- Cas contrat ---
            } elseif ($is_contract_payment && $reservation) {

                // Validation simple : vérifier que les champs essentiels existent
                if (empty($reservation['id_vehicule']) || empty($reservation['id_place'])) {
                    $error = "Données de réservation incomplètes.";
                } else {
                    try {
                        // On utilise une transaction pour insérer le contrat et mettre la place à jour
                        $pdo->beginTransaction();

                        // Générer un nouvel ID pour le contrat
                        $id_contrat = generer_id_contrat($pdo);

                        // Insérer dans la table contrat
                        $stmt = $pdo->prepare("
                            INSERT INTO contrat (id_contrat, id_vehicule, id_place, date_debut, date_fin, etat_contrat, type_contrat) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $id_contrat,
                            $reservation['id_vehicule'],
                            $reservation['id_place'],
                            $reservation['date_debut'],
                            $reservation['date_fin'],
                            'actif',
                            $reservation['type_contrat']
                        ]);

                        // Si le contrat est un ticketHoraire
                        if ($reservation['type_contrat'] === 'ticketHoraire') {
                            // Calculer la durée totale en heures
                            $date_debut = new DateTime($reservation['date_debut']);
                            $date_fin = new DateTime($reservation['date_fin']);
                            $interval = $date_debut->diff($date_fin);
                            $duree_totale = ($interval->days * 24) + $interval->h;

                            // Insérer dans la table ticketHoraire
                            $stmt = $pdo->prepare("
                                INSERT INTO ticketHoraire (id_ticket, tarif_horaire, duree_totale) 
                                VALUES (?, ?, ?)
                            ");
                            $stmt->execute([
                                $id_contrat,
                                1.5, // Tarif horaire fixe
                                $duree_totale
                            ]);
                        }

                        // Si le contrat est un abonnement
                        elseif ($reservation['type_contrat'] === 'abonnement') {
                            // Insérer dans la table abonnement
                            $stmt = $pdo->prepare("
                                INSERT INTO abonnement (id_abonnement, tarif_mensuel, renouvelable) 
                                VALUES (?, ?, ?)
                            ");
                            $stmt->execute([
                                $id_contrat,
                                50, // Tarif mensuel fixe
                                true // Renouvelable
                            ]);
                        }

                        // Mettre à jour la disponibilité de la place
                        $stmt = $pdo->prepare("UPDATE place SET est_dispo = false WHERE id_place = ?");
                        $stmt->execute([$reservation['id_place']]);

                        // Commit de la transaction
                        $pdo->commit();

                        // Message de succès
                        $success = "
                            <div style='background-color: #d4edda; color: #155724; padding: 20px; border-radius: 8px; border: 1px solid #c3e6cb;'>
                                <h2 style='margin-top: 0;'>✅ Paiement du contrat effectué avec succès !</h2>
                                <p><strong>Montant payé :</strong> " . number_format($reservation['montant_p'] ?? 0, 2, ',', ' ') . " €</p>
                                <p><strong>Type de contrat :</strong> " . htmlspecialchars(ucfirst($reservation['type_contrat'] ?? '')) . "</p>
                                <p><strong>Véhicule :</strong> " . htmlspecialchars($reservation['vehicule_modele'] ?? 'Non spécifié') . "</p>
                                <p><strong>Parking :</strong> " . htmlspecialchars($reservation['parking_nom'] ?? 'Non spécifié') . "</p>
                                <p><strong>Date de début :</strong> " . htmlspecialchars($reservation['date_debut'] ?? '') . "</p>
                                <p><strong>Date de fin :</strong> " . htmlspecialchars($reservation['date_fin'] ?? '') . "</p>
                                <p><strong>Place attribuée :</strong> " . htmlspecialchars($reservation['id_place'] ?? 'Non spécifiée') . "</p>
                                <p><strong>ID du contrat :</strong> " . htmlspecialchars($id_contrat) . "</p>
                                <p>Votre contrat a été activé avec succès. Merci pour votre confiance !</p>
                            </div>
                            <div style='text-align: center; margin-top: 20px;'>
                                <a href='dashboard.php' style='padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; font-size: 16px;'>Retour au tableau de bord</a>
                            </div>
                        ";




                        // Supprimer les données de réservation de la session
                        unset($_SESSION['reservation']);

                    } catch (Exception $e) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        $error = "Erreur lors de la création du contrat.";
                        error_log("Erreur création contrat : " . $e->getMessage());
                    }
                }
            } else {
                $error = "Aucune action de paiement spécifiée.";
            }

        } catch (Exception $e) {
            $error = "Erreur lors du traitement du paiement : " . $e->getMessage();
            // logger pour debug
            error_log("Erreur paiement général : " . $e->getMessage());
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
        <?= $success ?>
    <?php else: ?>
        <form method="POST" action="paiement.php<?= $is_penalty_payment && $get_id_penalite ? '?from=penalite&id_penalite=' . urlencode($get_id_penalite) . '&montant_p=' . urlencode($get_montant_p) : ($is_contract_payment ? '?from=contrat' : '') ?>">
            <h3>Informations de paiement</h3>

            <div style="margin-bottom: 15px;">
                <label for="numero_carte">Numéro de carte :</label>
                <input type="text" id="numero_carte" name="numero_carte" value="<?= htmlspecialchars($_POST['numero_carte'] ?? '') ?>" placeholder="1234567812345678" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="date_expiration">Date d'expiration (MM/AA) :</label>
                <input type="text" id="date_expiration" name="date_expiration" value="<?= htmlspecialchars($_POST['date_expiration'] ?? '') ?>" placeholder="MM/AA" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>

            <div style="margin-bottom: 15px;">
                <label for="cvv">CVV :</label>
                <input type="text" id="cvv" name="cvv" value="<?= htmlspecialchars($_POST['cvv'] ?? '') ?>" placeholder="123" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>

            <!-- Champs cachés dynamiques -->
            <?php if ($is_penalty_payment): ?>
                <!-- On prend d'abord POST si on revient après erreur, sinon GET si la page a été appelée avec ?id_penalite -->
                <input type="hidden" name="id_penalite" value="<?= htmlspecialchars($_POST['id_penalite'] ?? $get_id_penalite ?? '') ?>">
                <input type="hidden" name="montant_p" value="<?= htmlspecialchars($_POST['montant_p'] ?? $get_montant_p ?? '') ?>">
            <?php endif; ?>

            <input type="hidden" name="from" value="<?= htmlspecialchars($from ?? '') ?>">

            <button type="submit" style="padding: 10px 20px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;">Valider</button>
        </form>
    <?php endif; ?>
</div>

<?php include 'include/footer.inc.php'; ?>
