<?php

/**
 * Génère un nouvel identifiant unique pour un client.
 *
 * Cherche l'ID client le plus élevé dans la base de données et ajoute 1.
 * Format : CLxxxxxx (CL suivi de 6 chiffres, ex : CL000001)
 *
 * @param PDO $pdo La connexion à la base de données.
 * @return string L'identifiant unique du client généré.
 */
function generer_id_client($pdo) {
    // On trie par la partie numérique du code
    $stmt = $pdo->prepare("
        SELECT id_client 
        FROM client 
        ORDER BY CAST(SUBSTRING(id_client, 3) AS INTEGER) DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return "CL00001";
    }

    $last_id = $row["id_client"]; 
    $numero = intval(substr($last_id, 2)) + 1;

    return "CL" . str_pad($numero, 5, "0", STR_PAD_LEFT);
}

/**
 * Génère un nouvel identifiant unique pour un contrat.
 *
 * Cherche l'ID contrat le plus élevé dans la base de données et ajoute 1.
 * Format : CTxxxxxx (CT suivi de 6 chiffres, ex : CT000001)
 *
 * @param PDO $pdo La connexion à la base de données.
 * @return string L'identifiant unique du contrat généré.
 */
function generer_id_contrat($pdo) {
    $stmt = $pdo->prepare("
        SELECT id_contrat
        FROM contrat
        ORDER BY CAST(SUBSTRING(id_contrat, 3) AS INTEGER) DESC
        LIMIT 1
    ");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return "CT00001";
    }

    $last_id = $row["id_contrat"];   // CT00421
    $numero = intval(substr($last_id, 2)) + 1;

    return "CT" . str_pad($numero, 5, "0", STR_PAD_LEFT);
}
/**
 * Génère un nouvel identifiant unique pour un paiement.
 *
 * Cherche l'ID paiement le plus élevé dans la base de données et ajoute 1.
 * Format : PAxxxxxx (PA suivi de 6 chiffres, ex : PA000001)
 *
 * @param PDO $pdo La connexion à la base de données.
 * @return string L'identifiant unique du paiement généré.
 */
function generer_id_paiement($pdo) {
    // Requête pour récupérer le plus grand id_paiement
    $stmt = $pdo->prepare("
        SELECT id_paiement
        FROM paiement
        ORDER BY CAST(SUBSTRING(id_paiement, 4) AS INTEGER) DESC
        LIMIT 1
    ");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si aucun id_paiement n'existe, commencer à "PMT0001"
    if (!$row) {
        return "PMT0001";
    }

    // Récupérer le dernier id_paiement
    $last_id = $row["id_paiement"]; // Exemple : "PMT0008"

    // Extraire la partie numérique et incrémenter
    $numero = intval(substr($last_id, 3)) + 1;

    // Générer le nouvel id_paiement avec 4 chiffres (exemple : "PMT0009")
    return "PMT" . str_pad($numero, 4, "0", STR_PAD_LEFT);
}

/**
 * Récupère les informations d'un utilisateur par son ID.
 *
 * @param PDO $pdo La connexion à la base de données.
 * @param string $user_id L'identifiant unique de l'utilisateur.
 * @return array|null Un tableau associatif contenant les informations de l'utilisateur,
 *                    ou null si l'utilisateur n'existe pas.
 */
function get_user_info($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT nom, prenom, date_de_naissance, num_telephone, adresse_mail FROM client WHERE id_client = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Met à jour les informations d'un utilisateur dans la base de données.
 *
 * Cette fonction met à jour uniquement les champs fournis (les champs vides sont ignorés).
 * Si le mot de passe est fourni, il est automatiquement haché avec BCRYPT.
 *
 * @param PDO $pdo La connexion à la base de données.
 * @param string $user_id L'identifiant unique de l'utilisateur.
 * @param string $nom (Optionnel) Le nouveau nom de l'utilisateur.
 * @param string $prenom (Optionnel) Le nouveau prénom de l'utilisateur.
 * @param string $date_de_naissance (Optionnel) La nouvelle date de naissance (format YYYY-MM-DD).
 * @param string $telephone (Optionnel) Le nouveau numéro de téléphone.
 * @param string $email (Optionnel) La nouvelle adresse email.
 * @param string $password (Optionnel) Le nouveau mot de passe (sera haché automatiquement).
 * @return bool true si la mise à jour a réussi, false sinon.
 */
function update_user_info($pdo, $user_id, $nom, $prenom, $date_de_naissance, $telephone, $email, $password) {
    $query = "UPDATE client SET";
    $params = [];
    $updates = [];

    if (!empty($nom)) {
        $updates[] = "nom = ?";
        $params[] = $nom;
    }
    if (!empty($prenom)) {
        $updates[] = "prenom = ?";
        $params[] = $prenom;
    }
    if (!empty($date_de_naissance)) {
        $updates[] = "date_de_naissance = ?";
        $params[] = $date_de_naissance;
    }
    if (!empty($telephone)) {
        $updates[] = "num_telephone = ?";
        $params[] = $telephone;
    }
    if (!empty($email)) {
        $updates[] = "adresse_mail = ?";
        $params[] = $email;
    }
    if (!empty($password)) {
        // Hacher le mot de passe avant de l'insérer
        $updates[] = "password = ?";
        $params[] = password_hash($password, PASSWORD_BCRYPT);
    }

    // Si aucun champ n'est à mettre à jour, retourner false
    if (empty($updates)) {
        return false;
    }

    $query .= " " . implode(", ", $updates) . " WHERE id_client = ?";
    $params[] = $user_id;

    $stmt = $pdo->prepare($query);
    return $stmt->execute($params);
}

/**
 * Récupère tous les véhicules d'un utilisateur.
 *
 * @param PDO $pdo La connexion à la base de données.
 * @param string $user_id L'identifiant unique de l'utilisateur.
 * @return array Un tableau de tableaux associatifs contenant les informations
 *               de chaque véhicule, ou un tableau vide si l'utilisateur n'a pas de véhicules.
 */
function get_user_vehicles($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT id_vehicule, modele FROM vehicule WHERE id_client = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function delete_vehicle($pdo, $id_vehicule) {
    $stmt = $pdo->prepare("DELETE FROM vehicule WHERE id_vehicule = ?");
    return $stmt->execute([$id_vehicule]);
}

/**
 * Attribue une place de parking disponible à un véhicule selon son type.
 *
 * Recherche une place disponible dans le parking spécifié qui correspond
 * au type de véhicule (voiture, moto, camion, utilitaire, bus).
 *
 * @param PDO $pdo La connexion à la base de données.
 * @param int $id_parking L'identifiant du parking.
 * @param string $type_vehicule Le type de véhicule (voiture, moto, camion, utilitaire, bus).
 * @return int|null L'identifiant de la place attribuée, ou null si aucune place n'est disponible.
 */
function attribuer_place($pdo, $id_parking, $type_vehicule) {
    // Requête pour trouver une place disponible correspondant au type de véhicule
    $stmt = $pdo->prepare("
        SELECT id_place 
        FROM place 
        WHERE id_parking = ? 
          AND type_place = ? 
          AND est_dispo = true 
        LIMIT 1
    ");
    $stmt->execute([$id_parking, $type_vehicule]);
    $place = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($place) {
        // Marquer la place comme occupée
        $stmt = $pdo->prepare("UPDATE place SET est_dispo = false WHERE id_place = ?");
        $stmt->execute([$place['id_place']]);
        return $place['id_place'];
    }

    // Retourner null si aucune place n'est disponible
    return null;
}

function get_active_subscriptions($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT c.id_contrat, c.id_place, c.date_debut, c.date_fin, p.nom AS parking_nom
        FROM contrat c
        JOIN place pl ON c.id_place = pl.id_place
        JOIN parking p ON pl.id_parking = p.id_parking
        WHERE c.id_vehicule IN (SELECT id_vehicule FROM vehicule WHERE id_client = ?)
          AND c.type_contrat = 'abonnement'
          AND c.etat_contrat = 'actif'
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function cancel_subscription($pdo, $id_contrat, $id_place) {
    try {
        $pdo->beginTransaction();

        // Mettre à jour l'état du contrat à "résilié"
        $stmt = $pdo->prepare("UPDATE contrat SET etat_contrat = 'résilié' WHERE id_contrat = ?");
        $stmt->execute([$id_contrat]);

        // Rendre la place disponible
        $stmt = $pdo->prepare("UPDATE place SET est_dispo = true WHERE id_place = ?");
        $stmt->execute([$id_place]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * Calcule la date et l'heure de fin d'un contrat.
 *
 * Ajoute une durée spécifiée (en heures ou en semaines) à une date de début.
 *
 * @param string $start_date La date de début (format 'Y-m-d' ou 'Y-m-d H:i').
 * @param int $duration La durée à ajouter.
 * @param string $unit L'unité de durée ('hours' ou 'weeks').
 * @return string La date de fin au format 'Y-m-d\TH:i'.
 */
function calculate_end_date($start_date, $duration, $unit) {
    $date = new DateTime($start_date);

    if ($unit === 'hours') {
        $date->modify("+{$duration} hours");
    } elseif ($unit === 'weeks') {
        $date->modify("+{$duration} weeks");
    }

    return $date->format('Y-m-d\TH:i');
}

/**
 * Récupère l'historique des accès d'un utilisateur au parking.
 *
 * Retourne tous les scans de codes QR effectués par les véhicules de l'utilisateur,
 * trié par date décroissante (plus récent en premier).
 *
 * @param PDO $pdo La connexion à la base de données.
 * @param string $user_id L'identifiant unique de l'utilisateur.
 * @return array Un tableau de tableaux associatifs contenant :
 *               - id_contrat : ID du contrat utilisé
 *               - heure_scanne : Date/heure du scan
 *               - type_de_borne : Type de borne ('entree' ou 'sortie')
 *               - vehicule_modele : Modèle du véhicule
 *               - parking_nom : Nom du parking
 *               Ou un tableau vide si aucun historique.
 */
function get_user_history($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT v.id_contrat, v.heure_scanne, b.type_de_borne, vh.modele AS vehicule_modele, p.nom AS parking_nom
        FROM verifie v
        JOIN borne b ON v.id_borne = b.id_borne
        JOIN contrat c ON v.id_contrat = c.id_contrat
        JOIN vehicule vh ON c.id_vehicule = vh.id_vehicule
        JOIN place pl ON c.id_place = pl.id_place
        JOIN parking p ON pl.id_parking = p.id_parking
        WHERE c.id_vehicule IN (SELECT id_vehicule FROM vehicule WHERE id_client = ?)
        ORDER BY v.heure_scanne DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère toutes les pénalités d'un utilisateur.
 *
 * @param PDO $pdo La connexion à la base de données.
 * @param string $user_id L'identifiant unique de l'utilisateur.
 * @return array Un tableau de tableaux associatifs contenant les informations
 *               de chaque pénalité, ou un tableau vide si l'utilisateur n'a pas de pénalités.
 */function get_user_penalties($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT p.id_penalite, p.id_contrat, p.montant_p, p.description, p.date_creation
        FROM penalite p
        JOIN contrat c ON p.id_contrat = c.id_contrat
        JOIN vehicule v ON c.id_vehicule = v.id_vehicule
        WHERE v.id_client = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Supprime une pénalité de la base de données.
 *
 * @param PDO $pdo La connexion à la base de données.
 * @param string $id_penalite L'identifiant unique de la pénalité à supprimer.
 * @return bool true si la suppression a réussi, false sinon.
 */function delete_penalty($pdo, $id_penalite) {
    $stmt = $pdo->prepare("DELETE FROM penalite WHERE id_penalite = ?");
    return $stmt->execute([$id_penalite]);
}

/**
 * Vérifie si une pénalité existe dans la base de données.
 *
 * @param PDO $pdo La connexion à la base de données.
 * @param string $id_penalite L'identifiant unique de la pénalité.
 * @return array|false Un tableau associatif contenant les informations de la pénalité,
 *                     ou false si la pénalité n'existe pas.
 */function is_penalty_payment($pdo, $id_penalite) {
    $stmt = $pdo->prepare("SELECT * FROM penalite WHERE id_penalite = ?");
    $stmt->execute([$id_penalite]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Récupère les détails complets d'un contrat.
 *
 * Retourne les informations du contrat, du parking, du véhicule et du client associés.
 *
 * @param PDO $pdo La connexion à la base de données.
 * @param string $id_contrat L'identifiant unique du contrat.
 * @return array|false Un tableau associatif contenant :
 *                     - id_contrat, date_debut, date_fin, etat_contrat
 *                     - parking_nom, vehicule_modele, client_nom, client_prenom, id_parking
 *                     Ou false si le contrat n'existe pas.
 */
function get_contract_details($pdo, $id_contrat) {
    $stmt = $pdo->prepare("
        SELECT c.id_contrat, c.date_debut, c.date_fin, c.etat_contrat, p.nom AS parking_nom, v.modele AS vehicule_modele, cl.nom AS client_nom, cl.prenom AS client_prenom, pl.id_parking
        FROM contrat c
        JOIN place pl ON c.id_place = pl.id_place
        JOIN parking p ON pl.id_parking = p.id_parking
        JOIN vehicule v ON c.id_vehicule = v.id_vehicule
        JOIN client cl ON v.id_client = cl.id_client
        WHERE c.id_contrat = ?
    ");
    $stmt->execute([$id_contrat]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
/**
 * Enregistre ou met à jour les détails de la carte de crédit d'un utilisateur.
 *
 * @param PDO $pdo La connexion à la base de données.
 * @param string $user_id L'identifiant unique de l'utilisateur.
 * @param string $numero_carte Le numéro de la carte de crédit (16 chiffres).
 * @param string $date_expiration La date d'expiration de la carte (format MM/AA).
 * @param string $cvv Le code de vérification CVV (3 chiffres).
 * @return bool true si l'enregistrement a réussi, false sinon.
 */
function update_card_details($pdo, $user_id, $numero_carte, $date_expiration, $cvv) {
    // Format pour un tableau PostgreSQL (VARCHAR[])
    $detail_carte = '{"' . $numero_carte . '","' . $date_expiration . '","' . $cvv . '"}';

    $stmt = $pdo->prepare("UPDATE client SET detail_carte = ? WHERE id_client = ?");
    return $stmt->execute([$detail_carte, $user_id]);
}

/**
 * Récupère tous les contrats d'un utilisateur.
 *
 * @param PDO $pdo La connexion à la base de données.
 * @param string $user_id L'identifiant unique de l'utilisateur.
 * @return array Un tableau de tableaux associatifs contenant les informations
 *               de chaque contrat, ou un tableau vide si l'utilisateur n'a pas de contrats.
 */
function get_user_contracts($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT c.id_contrat, c.date_debut, c.date_fin, c.etat_contrat, p.nom AS parking_nom, v.modele AS vehicule_modele
        FROM contrat c
        JOIN place pl ON c.id_place = pl.id_place
        JOIN parking p ON pl.id_parking = p.id_parking
        JOIN vehicule v ON c.id_vehicule = v.id_vehicule
        WHERE v.id_client = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Calcule le prix d'un contrat en fonction de son type et de sa durée.
 *
 * - Pour un ticket horaire : 1,5 € par heure.
 * - Pour un abonnement : 10 € par semaine.
 *
 * @param string $type_contrat Le type de contrat ('ticketHoraire' ou 'abonnement').
 * @param int $duree La durée du contrat (en heures pour un ticket horaire, en semaines pour un abonnement).
 * @return float Le prix total du contrat.
 * @throws Exception Si le type de contrat est invalide.
 */
function calculate_contract_price($type_contrat, $duree) {
    if ($type_contrat === 'ticketHoraire') {
        // Prix par heure pour un ticket horaire
        $prix_par_heure = 1.5;
        return $duree * $prix_par_heure;
    } elseif ($type_contrat === 'abonnement') {
        // Prix par semaine pour un abonnement
        $prix_par_semaine = 10.0;
        return $duree * $prix_par_semaine;
    } else {
        throw new Exception("Type de contrat invalide.");
    }
}

/**
 * Récupère le type d'un véhicule à partir de son identifiant.
 *
 * @param PDO $pdo La connexion à la base de données.
 * @param int $id_vehicule L'identifiant du véhicule.
 * @return string|null Le type du véhicule (voiture, moto, camion, utilitaire, bus), ou null si le véhicule n'existe pas.
 */
function get_vehicle_type($pdo, $id_vehicule) {
    $stmt = $pdo->prepare("SELECT type FROM vehicule WHERE id_vehicule = ?");
    $stmt->execute([$id_vehicule]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['type'] : null;
}

/**
 * Génère une URL vers une image QR code contenant les infos d'accès au parking.
 *
 * Format du QR : PK000001;CT000001 (id_parking;id_contrat)
 *
 * @param int $id_parking L'identifiant du parking.
 * @param string $id_contrat L'identifiant du contrat.
 * @return string L'URL de l'image QR code.
 */
function generer_qr_code_url($id_parking, $id_contrat) {
    // Format simple : PK000001;CT000001
    $data = $id_parking . ";" . $id_contrat;

    // Encoder les données en URL
    $encoded_data = urlencode($data);

    // URL de l'API QR Code
    $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . $encoded_data;

    return $qr_url;
}

