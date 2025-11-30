<?php
/* penalite.php
   Affiche les pénalités de l'utilisateur connecté et permet de les régler.
*/

session_start();
require_once 'config/db_config.php';
require_once 'include/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$pdo = get_db_connection();
$user_id = $_SESSION['user_id'];

// Récupérer les pénalités de l'utilisateur connecté
$penalties = get_user_penalties($pdo, $user_id);

include 'include/header.inc.php';
?>

<div style="max-width: 1000px; margin: 50px auto;">
    <h2>Mes pénalités</h2>

    <?php if (!empty($penalties)): ?>
        <table style="width: 100%; border-collapse: collapse; margin-top: 15px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <thead>
                <tr style="background-color: #f8f9fa;">
                    <th style="padding: 12px; border: 1px solid #ddd;">Montant (€)</th>
                    <th style="padding: 12px; border: 1px solid #ddd;">Description</th>
                    <th style="padding: 12px; border: 1px solid #ddd;">Date de création</th>
                    <th style="padding: 12px; border: 1px solid #ddd;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($penalties as $penalty): ?>
                    <tr>
                        <td style="padding: 12px; border: 1px solid #ddd;"><?= number_format($penalty['montant_p'], 2) ?></td>
                        <td style="padding: 12px; border: 1px solid #ddd;"><?= htmlspecialchars($penalty['description']) ?></td>
                        <td style="padding: 12px; border: 1px solid #ddd;"><?= htmlspecialchars($penalty['date_creation']) ?></td>
                        <td style="padding: 12px; border: 1px solid #ddd;">
                            <form method="POST" action="paiement.php" style="display: inline;">
                                <input type="hidden" name="id_penalite" value="<?= htmlspecialchars($penalty['id_penalite']) ?>">
                                <input type="hidden" name="montant_p" value="<?= htmlspecialchars($penalty['montant_p']) ?>">
                                <input type="hidden" name="from" value="penalite">
                                <button type="submit" style="padding: 8px 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Régler</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color: #6c757d; font-style: italic; margin-top: 15px;">Aucune pénalité en attente.</p>
    <?php endif; ?>
</div>

<?php include 'include/footer.inc.php'; ?>

