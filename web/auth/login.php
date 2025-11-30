<?php
session_start();
require_once '../config/db_config.php';

$error = '';
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "L'email et le mot de passe sont obligatoires.";
    } else {
        try {
            $pdo = get_db_connection();
            $stmt = $pdo->prepare("SELECT id_client, password, nom, prenom FROM client WHERE adresse_mail = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id_client'];
                $_SESSION['user_nom'] = $user['nom'];
                $_SESSION['user_prenom'] = $user['prenom'];
                header("Location: ../dashboard.php");
                exit();
            } else {
                $error = "Email ou mot de passe incorrect.";
            }
        } catch (Exception $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

include '../include/header.inc.php';
?>

<div style="background: #ffffff; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #0f3460; padding: 40px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.4); width: 100%; max-width: 450px; border-top: 3px solid #00d4ff;">
        
        <h1 style="text-align: center; color: #00d4ff; margin-top: 0;">Connexion</h1>
        <p style="text-align: center; color: #b0b0b0; margin-bottom: 30px;">Accédez à votre compte Parking IT</p>

        <?php if ($success): ?>
            <div style="background-color: #2d3d2d; color: #6bff6b; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #6bff6b;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background-color: #3d2d2d; color: #ff6b6b; padding: 15px; border-radius: 6px; margin-bottom: 20px; border-left: 4px solid #ff6b6b;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" style="display: flex; flex-direction: column;">
            <label style="color: #00d4ff; font-weight: bold; margin-bottom: 5px;">Email *</label>
            <input type="email" name="email" required style="padding: 12px; margin-bottom: 15px; border: 1px solid #00d4ff; border-radius: 6px; font-size: 1em; background: #1a1a2e; color: #e0e0e0;">

            <label style="color: #00d4ff; font-weight: bold; margin-bottom: 5px;">Mot de passe *</label>
            <input type="password" name="password" required style="padding: 12px; margin-bottom: 25px; border: 1px solid #00d4ff; border-radius: 6px; font-size: 1em; background: #1a1a2e; color: #e0e0e0;">

            <button type="submit" style="background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%); color: #1a1a2e; padding: 12px; border: none; border-radius: 6px; font-size: 1.1em; font-weight: bold; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(0, 212, 255, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                Se connecter
            </button>
        </form>

        <p style="text-align: center; color: #b0b0b0; margin-top: 20px;">
            Pas de compte? <a href="register.php" style="color: #00d4ff; text-decoration: none; font-weight: bold;">S'inscrire</a>
        </p>
        <p style="text-align: center; color: #b0b0b0; margin-top: 10px;">
            <a href="../index.php" style="color: #707070; text-decoration: none; font-size: 0.9em;">Retour</a>
        </p>
    </div>
</div>

<?php include '../include/footer.inc.php'; ?>