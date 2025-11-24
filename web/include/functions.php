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