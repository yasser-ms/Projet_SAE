<?php
/* register.php
   Formulaire d'inscription avec hachage du mot de passe.
*/

session_start();
require_once '../config/db_config.php';
require_once '../include/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $date_naissance = $_POST['date_naissance'] ?? '';
    $email = $_POST['email'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validation
    if (empty($nom) || empty($prenom) || empty($date_naissance) || empty($email) || empty($telephone) || empty($password)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif ($password !== $password_confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse email n'est pas valide.";
    } else {
        try {
            $pdo = get_db_connection();

            // Vérifier si l'email existe
            $stmt = $pdo->prepare("SELECT id_client FROM client WHERE adresse_mail = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Cet email est déjà utilisé.";
            } else {
                // Générer un nouvel ID client
                $id_client = generer_id_client($pdo);

                // Hacher le mot de passe
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                // Insérer le nouvel utilisateur
                $stmt = $pdo->prepare("
                    INSERT INTO client (id_client, nom, prenom, date_de_naissance, num_telephone, adresse_mail, password)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $id_client,
                    $nom,
                    $prenom,
                    $date_naissance,
                    $telephone,
                    $email,
                    $hashed_password
                ]);

                // Rediriger vers login.php avec message de succès
                $_SESSION['success'] = "Inscription réussie! Connectez-vous pour accéder à votre compte.";
                header("Location: login.php");
                exit();
            }
        } catch (Exception $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

include '../include/header.inc.php';
?>

<div style="background: #ffffff; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #0f3460; padding: 40px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.4); width: 100%; max-width: 500px; border-top: 3px solid #00d4ff;">
        
        <h1 style="text-align: center; color: #00d4ff; margin-top: 0;">Créer un compte</h1>
        <p style="text-align: center; color: #b0b0b0; margin-bottom: 30px;">Rejoignez Parking IT en quelques étapes</p>

        <?php if ($error): ?>
            <div style="background-color: #3d2d2d; color: #ff6b6b; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #ff6b6b;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" style="display: flex; flex-direction: column;">
            <label style="color: #00d4ff; font-weight: bold; margin-bottom: 5px;">Nom *</label>
            <input type="text" name="nom" required style="padding: 12px; margin-bottom: 15px; border: 1px solid #00d4ff; border-radius: 6px; font-size: 1em; background: #1a1a2e; color: #e0e0e0;">

            <label style="color: #00d4ff; font-weight: bold; margin-bottom: 5px;">Prénom *</label>
            <input type="text" name="prenom" required style="padding: 12px; margin-bottom: 15px; border: 1px solid #00d4ff; border-radius: 6px; font-size: 1em; background: #1a1a2e; color: #e0e0e0;">

            <label style="color: #00d4ff; font-weight: bold; margin-bottom: 5px;">Date de naissance *</label>
            <input type="date" name="date_naissance" required style="padding: 12px; margin-bottom: 15px; border: 1px solid #00d4ff; border-radius: 6px; font-size: 1em; background: #1a1a2e; color: #e0e0e0;">

            <label style="color: #00d4ff; font-weight: bold; margin-bottom: 5px;">Email *</label>
            <input type="email" name="email" required style="padding: 12px; margin-bottom: 15px; border: 1px solid #00d4ff; border-radius: 6px; font-size: 1em; background: #1a1a2e; color: #e0e0e0;">

            <label style="color: #00d4ff; font-weight: bold; margin-bottom: 5px;">Téléphone *</label>
            <input type="tel" name="telephone" required style="padding: 12px; margin-bottom: 15px; border: 1px solid #00d4ff; border-radius: 6px; font-size: 1em; background: #1a1a2e; color: #e0e0e0;">

            <label style="color: #00d4ff; font-weight: bold; margin-bottom: 5px;">Mot de passe (min. 8 caractères) *</label>
            <input type="password" name="password" required style="padding: 12px; margin-bottom: 15px; border: 1px solid #00d4ff; border-radius: 6px; font-size: 1em; background: #1a1a2e; color: #e0e0e0;">

            <label style="color: #00d4ff; font-weight: bold; margin-bottom: 5px;">Confirmer le mot de passe *</label>
            <input type="password" name="password_confirm" required style="padding: 12px; margin-bottom: 25px; border: 1px solid #00d4ff; border-radius: 6px; font-size: 1em; background: #1a1a2e; color: #e0e0e0;">

            <button type="submit" style="background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%); color: #1a1a2e; padding: 12px; border: none; border-radius: 6px; font-size: 1.1em; font-weight: bold; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(0, 212, 255, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                S'inscrire
            </button>
        </form>

        <p style="text-align: center; color: #b0b0b0; margin-top: 20px;">
            Vous avez déjà un compte? <a href="login.php" style="color: #00d4ff; text-decoration: none; font-weight: bold;">Se connecter</a>
        </p>
    </div>
</div>

<?php include '../include/footer.inc.php'; ?>

