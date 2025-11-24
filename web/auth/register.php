<?php

session_start();
require_once '../config/db_config.php';
require_once '../include/functions.php'; // Inclure la fonction generer_id_client

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $date_naissance = $_POST['date_naissance'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validation des champs
    if (empty($nom) || empty($prenom) || empty($date_naissance) || empty($email) || empty($telephone) || empty($password) || empty($password_confirm)) {
        $_SESSION['error'] = "Veuillez remplir tous les champs.";
        header("Location: ../index.php");
        exit();
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_naissance)) {
        $_SESSION['error'] = "La date de naissance doit être au format AAAA-MM-JJ.";
        header("Location: ../index.php");
        exit();
    }

    if ($password !== $password_confirm) {
        $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
        header("Location: ../index.php");
        exit();
    }

    if (strlen($password) < 6) {
        $_SESSION['error'] = "Le mot de passe doit contenir au moins 6 caractères.";
        header("Location: ../index.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Adresse email invalide.";
        header("Location: ../index.php");
        exit();
    }

    try {
        $pdo = get_db_connection();

        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare("SELECT id_client FROM client WHERE adresse_mail = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Cet email est déjà enregistré.";
            header("Location: ../index.php");
            exit();
        }

        // Générer un id_client unique
        $id_client = generer_id_client($pdo);

        // Hacher le mot de passe pour plus de sécurité
       // $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Insérer un nouvel utilisateur dans la table client
        $stmt = $pdo->prepare("INSERT INTO client (id_client, nom, prenom, date_de_naissance, adresse_mail, num_telephone, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id_client, $nom, $prenom, $date_naissance, $email, $telephone, $password]);

        $_SESSION['success'] = "Compte créé avec succès ! Veuillez vous connecter.";
        header("Location: ../index.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de l'inscription : " . $e->getMessage();
        header("Location: ../index.php");
        exit();
    }
}
?>
<?php include '../include/header.inc.php'; ?>

<h2>Inscription en cours...</h2>

<?php include '../include/footer.inc.php'; ?>

