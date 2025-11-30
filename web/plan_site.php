<?php
/* plan_site.php
   Affiche le plan du site avec tous les liens disponibles.
*/
session_start();
require_once 'config/db_config.php';
?>

<?php include 'include/header.inc.php'; ?>

<div style="max-width: 1000px; margin: 50px auto;">
    <h1 style="text-align: center; margin-bottom: 40px;">Plan du Site</h1>

    <div style="background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        

        <!-- Section : Authentification -->
        <div style="margin-bottom: 30px;">
            <h2 style="color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px;">Authentification</h2>
            <ul style="list-style: none; padding: 0;">
                <li style="margin-bottom: 10px;">
                    <a href="auth/login.php" style="color: #007bff; text-decoration: none; font-size: 16px;">Connexion</a>
                </li>
                <li style="margin-bottom: 10px;">
                    <a href="auth/register.php" style="color: #007bff; text-decoration: none; font-size: 16px;">Inscription</a>
                </li>
                <li style="margin-bottom: 10px;">
                    <a href="logout.php" style="color: #007bff; text-decoration: none; font-size: 16px;">Déconnexion</a>
                </li>
            </ul>
        </div>

        <!-- Section : Espace Client (nécessite une connexion) -->
        <div style="margin-bottom: 30px;">
            <h2 style="color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px;">Espace Client</h2>
            <ul style="list-style: none; padding: 0;">
                <li style="margin-bottom: 10px;">
                    <a href="dashboard.php" style="color: #007bff; text-decoration: none; font-size: 16px;">Tableau de bord</a>
                </li>
                <li style="margin-bottom: 10px;">
                    <a href="parametres.php" style="color: #007bff; text-decoration: none; font-size: 16px;">Paramètres du compte</a>
                </li>
                <li style="margin-bottom: 10px;">
                    <a href="vehicule.php" style="color: #007bff; text-decoration: none; font-size: 16px;">Mes véhicules</a>
                </li>
            </ul>
        </div>

        <!-- Section : Réservations et Paiements -->
        <div style="margin-bottom: 30px;">
            <h2 style="color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px;">Réservations et Paiements</h2>
            <ul style="list-style: none; padding: 0;">
                <li style="margin-bottom: 10px;">
                    <a href="achats.php" style="color: #007bff; text-decoration: none; font-size: 16px;">Achats et Réservations</a>
                </li>
                <li style="margin-bottom: 10px;">
                    <a href="paiement.php" style="color: #007bff; text-decoration: none; font-size: 16px;">Paiement</a>
                </li>
                <li style="margin-bottom: 10px;">
                    <a href="penalite.php" style="color: #007bff; text-decoration: none; font-size: 16px;">Pénalités</a>
                </li>
            </ul>
        </div>

        <!-- Section : Informations -->
        <div style="margin-bottom: 30px;">
            <h2 style="color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px;">Informations</h2>
            <ul style="list-style: none; padding: 0;">
                <li style="margin-bottom: 10px;">
                    <a href="plan_site.php" style="color: #007bff; text-decoration: none; font-size: 16px;">Plan du site</a>
                </li>
            </ul>
        </div>

        <!-- Section : Résumé -->
        <div style="background-color: #f8f9fa; padding: 20px; border-radius: 6px; margin-top: 40px;">
            <h3 style="color: #333;">Résumé des pages</h3>
            <p style="color: #666; line-height: 1.8;">
                <strong>Total de pages :</strong> 10+ pages<br>
                <strong>Pages publiques :</strong> Accueil, Connexion, Inscription<br>
                <strong>Pages protégées :</strong> Tableau de bord, Véhicules, Achats, Paiement, Pénalités, Paramètres
            </p>
        </div>

    </div>
</div>

<?php include 'include/footer.inc.php'; ?>