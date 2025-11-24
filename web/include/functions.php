<?php
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
/* 
function generer_id_place($pdo) {
    $stmt = $pdo->prepare("
        SELECT id_place
        FROM place
        ORDER BY CAST(SUBSTRING(id_place, 2) AS INTEGER) DESC
        LIMIT 1
    ");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return "P001";
    }

    $last_id = $row["id_place"];   // P014
    $numero = intval(substr($last_id, 1)) + 1;

    return "P" . str_pad($numero, 3, "0", STR_PAD_LEFT);
}
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

function get_user_info($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT nom, prenom, date_de_naissance, num_telephone, adresse_mail FROM client WHERE id_client = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

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
        $updates[] = "password = ?";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
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

function get_user_vehicles($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT id_vehicule, modele FROM vehicule WHERE id_client = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function delete_vehicle($pdo, $id_vehicule) {
    $stmt = $pdo->prepare("DELETE FROM vehicule WHERE id_vehicule = ?");
    return $stmt->execute([$id_vehicule]);
}

function attribuer_place($pdo, $id_parking) {
    // Rechercher une place disponible dans le parking sélectionné
    $stmt = $pdo->prepare("SELECT id_place FROM place WHERE id_parking = ? AND est_dispo = true LIMIT 1");
    $stmt->execute([$id_parking]);
    $place = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($place) {
        // Si une place est trouvée, retourner son ID
        return $place['id_place'];
    }

    // Si aucune place n'est disponible, retourner null
    return null;
}