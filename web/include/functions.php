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