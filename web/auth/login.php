<?php
session_start();
require_once '../config/db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Veuillez remplir tous les champs.";
        header("Location: ../index.php");
        exit();
    }

    try {
        $pdo = get_db_connection();
        $stmt = $pdo->prepare("SELECT id_client, nom, prenom, adresse_mail FROM client WHERE adresse_mail = ? AND password = ?");
        $stmt->execute([$email, $password]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $_SESSION['user_id'] = $user['id_client'];
            $_SESSION['user_nom'] = $user['nom'] . ' ' . $user['prenom'];
            $_SESSION['user_email'] = $user['adresse_mail'];
            $_SESSION['success'] = "Connexion réussie !";
            header("Location: ../dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "Email ou mot de passe incorrect.";
            header("Location: ../index.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur : " . $e->getMessage();
        header("Location: ../index.php");
        exit();
    }
}
?>
<?php include '../include/header.inc.php'; ?>

<h2>Connexion en cours...</h2>

<?php include '../include/footer.inc.php'; ?>