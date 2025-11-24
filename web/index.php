<?php
session_start();
require_once 'config/db_config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<?php include 'include/header.inc.php'; ?>
<main>

<div style="max-width: 600px; margin: 50px auto;">
    <?php if (isset($_SESSION['error'])): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            <?php echo htmlspecialchars($_SESSION['error']); ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
             <?php echo htmlspecialchars($_SESSION['success']); ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; gap: 20px;">
        <!-- Formulaire de Connexion -->
        <div style="flex: 1; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h2> Connexion</h2>
            <form method="POST" action="auth/login.php">
                <div style="margin-bottom: 15px;">
                    <label for="email_login">Email :</label>
                    <input type="email" id="email_login" name="email" required style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="password_login">Mot de passe :</label>
                    <input type="password" id="password_login" name="password" required style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <button type="submit" style="width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Se connecter</button>
            </form>
        </div>

        <!-- Formulaire d'Inscription -->
        <div style="flex: 1; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h2>Créer un compte</h2>
            <form method="POST" action="auth/register.php">
                <div style="margin-bottom: 15px;">
                    <label for="nom">Nom :</label>
                    <input type="text" id="nom" name="nom" required style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="prenom">Prénom :</label>
                    <input type="text" id="prenom" name="prenom" required style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="date_naissance">Date de naissance :</label>
                    <input type="date" id="date_naissance" name="date_naissance" required style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="email_register">Email :</label>
                    <input type="email" id="email_register" name="email" required style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="telephone">Téléphone :</label>
                    <input type="tel" id="telephone" name="telephone" required style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="password_register">Mot de passe :</label>
                    <input type="password" id="password_register" name="password" required style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label for="password_confirm">Confirmer le mot de passe :</label>
                    <input type="password" id="password_confirm" name="password_confirm" required style="width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <button type="submit" style="width: 100%; padding: 10px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">S'inscrire</button>
            </form>
        </div>
    </div>
</div>
    </main>

<?php include 'include/footer.inc.php'; ?>