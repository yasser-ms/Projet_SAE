<?php
/* index.php
Page d'accueil avec présentation du projet et redirection vers connexion/inscription.
*/
session_start();
require_once 'config/db_config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<?php include 'include/header.inc.php'; ?>

<main class="main-wrapper">
    <div class="main-container" style="background: #ffffff; min-height: 100vh; padding: 60px 20px;">
    
    <!-- Section Présentation du Projet -->
    <div style="max-width: 1200px; margin: 0 auto;">
        <div style="text-align: center; color: white; margin-bottom: 60px;">
           <h1 style="font-size: 3.5em; margin: 0 0 20px 0; font-weight: bold; color: #000; text-shadow: 1px 1px 2px rgba(0,0,0,0.25);">
    Parking IT
</h1>
<p style="font-size: 1.3em; margin: 0; color: #000; opacity: 0.95;">
    Gestion intelligente des parkings - Système de réservation et de contrôle d'accès
</p>

        </div>

        <!-- Conteneur Principal -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: center; margin-bottom: 60px;">
            
            <!-- Colonne Gauche : Présentation -->
            <div style="background: #0f3460; padding: 40px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.4); border-left: 5px solid #00d4ff;">
                <h2 style="color: #00d4ff; font-size: 2em; margin-top: 0;">À propos du projet</h2>
                
                <p style="color: #e0e0e0; font-size: 1.05em; line-height: 1.8; margin-bottom: 20px;">
                    <strong>Parking IT</strong> est une plateforme moderne de gestion de parkings développée par une équipe de 3 étudiants. 
                    Ce projet a été réalisé dans le cadre du module <strong>Base de Données et Réseaux</strong>.
                </p>

                <div style="background: #1a1a2e; padding: 20px; border-left: 4px solid #00d4ff; margin-bottom: 20px; border-radius: 6px;">
                    <h3 style="color: #00d4ff; margin-top: 0;">Objectifs du projet</h3>
                    <ul style="color: #b0b0b0; font-size: 0.95em; line-height: 1.8;">
                        <li>Permettre la réservation de places en ligne</li>
                        <li>Générer des codes QR pour l'accès rapide</li>
                        <li>Gérer les contrats (abonnements et tickets horaires)</li>
                        <li>Traiter les paiements et les pénalités</li>
                    </ul>
                </div>

                <div style="background: #1a1a2e; padding: 20px; border-left: 4px solid #ff006e; border-radius: 6px;">
                    <h3 style="color: #ff006e; margin-top: 0;">Équipe</h3>
                    <ul style="color: #b0b0b0; font-size: 0.95em; line-height: 1.8; list-style: none; padding: 0;">
                        <li><strong>Nadjib</strong> </li>
                        <li><strong>Omar</strong> </li>
                        <li><strong>Yasser</strong></li>
                    </ul>
                </div>
            </div>

            <!-- Colonne Droite : Formulaires -->
            <div>
                <div style="margin-bottom: 30px;">
                    <!-- Bouton Connexion -->
                    <a href="auth/login.php" style="
                        display: block;
                        background: linear-gradient(135deg, #00d4ff 0%, #0099cc 100%);
                        color: #1a1a2e;
                        padding: 20px;
                        border-radius: 12px;
                        text-align: center;
                        text-decoration: none;
                        font-size: 1.2em;
                        font-weight: bold;
                        box-shadow: 0 8px 20px rgba(0, 212, 255, 0.3);
                        transition: all 0.3s ease;
                        margin-bottom: 15px;
                        border: 2px solid transparent;
                    " 
                    onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 12px 30px rgba(0, 212, 255, 0.5)';"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 20px rgba(0, 212, 255, 0.3)';">
                        Se connecter
                    </a>

                    <!-- Bouton Inscription -->
                    <a href="auth/register.php" style="
                        display: block;
                        background: #0f3460;
                        color: #00d4ff;
                        padding: 20px;
                        border-radius: 12px;
                        text-align: center;
                        text-decoration: none;
                        font-size: 1.2em;
                        font-weight: bold;
                        border: 2px solid #00d4ff;
                        transition: all 0.3s ease;
                        box-shadow: 0 8px 20px rgba(0, 212, 255, 0.2);
                    "
                    onmouseover="this.style.backgroundColor='#00d4ff'; this.style.color='#1a1a2e'; this.style.boxShadow='0 12px 30px rgba(0, 212, 255, 0.4)';"
                    onmouseout="this.style.backgroundColor='#0f3460'; this.style.color='#00d4ff'; this.style.boxShadow='0 8px 20px rgba(0, 212, 255, 0.2)';">
                        Créer un compte
                    </a>
                </div>

                <!-- Statistiques -->
                <div style="background: #0f3460; padding: 25px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.4); text-align: center; border-top: 3px solid #00d4ff;">
                    <h3 style="color: #00d4ff; margin-top: 0;">Plateforme</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <div style="font-size: 2em; color: #00d4ff; font-weight: bold;">50+</div>
                            <div style="color: #b0b0b0; font-size: 0.9em;">Parkings</div>
                        </div>
                        <div>
                            <div style="font-size: 2em; color: #ff006e; font-weight: bold;">1000+</div>
                            <div style="color: #b0b0b0; font-size: 0.9em;">Places</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Caractéristiques -->
        <div style="background: #0f3460; padding: 40px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.4); margin-bottom: 40px; border-top: 3px solid #00d4ff;">
            <h2 style="color: #00d4ff; text-align: center; font-size: 2em; margin-top: 0;">Fonctionnalités principales</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-top: 30px;">
                
                <div style="text-align: center; padding: 20px; background: #1a1a2e; border-radius: 8px; border-left: 3px solid #00d4ff;">
                    <h3 style="color: #00d4ff;">Réservation Simple</h3>
                    <p style="color: #b0b0b0;">Réservez une place de parking en quelques clics, avec la possibilité de choisir votre parking et votre type de contrat.</p>
                </div>

                <div style="text-align: center; padding: 20px; background: #1a1a2e; border-radius: 8px; border-left: 3px solid #00d4ff;">
                    <h3 style="color: #00d4ff;">Codes QR</h3>
                    <p style="color: #b0b0b0;">Génération automatique de codes QR pour un accès rapide et sécurisé aux parkings.</p>
                </div>

                <div style="text-align: center; padding: 20px; background: #1a1a2e; border-radius: 8px; border-left: 3px solid #ff006e;">
                    <h3 style="color: #ff006e;">Paiements Sécurisés</h3>
                    <p style="color: #b0b0b0;">Système de paiement intégré et sécurisé pour tous vos réservations et contrats.</p>
                </div>

                <div style="text-align: center; padding: 20px; background: #1a1a2e; border-radius: 8px; border-left: 3px solid #00d4ff;">
                    <h3 style="color: #00d4ff;">Tableau de Bord</h3>
                    <p style="color: #b0b0b0;">Visualisez vos contrats, vos paiements et votre historique d'accès en temps réel.</p>
                </div>

                <div style="text-align: center; padding: 20px; background: #1a1a2e; border-radius: 8px; border-left: 3px solid #00d4ff;">
                    <h3 style="color: #00d4ff;">Gestion Véhicules</h3>
                    <p style="color: #b0b0b0;">Ajoutez et gérez vos véhicules avec un système de catégorisation intelligent.</p>
                </div>

                <div style="text-align: center; padding: 20px; background: #1a1a2e; border-radius: 8px; border-left: 3px solid #ff006e;">
                    <h3 style="color: #ff006e;">Gestion Pénalités</h3>
                    <p style="color: #b0b0b0;">Suivi transparent des amendes et des infractions de stationnement.</p>
                </div>

            </div>
        </div>

        <!-- Section Technologies -->
        <div style="background: #0f3460; padding: 40px; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.4); border-top: 3px solid #00d4ff;">
            <h2 style="color: #00d4ff; text-align: center; font-size: 2em; margin-top: 0;">Technologies utilisées</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 30px;">
                <div style="background: #1a1a2e; padding: 15px; border-radius: 8px; text-align: center; border-left: 3px solid #00d4ff;">
                    <strong style="color: #00d4ff;">Frontend</strong>
                    <p style="color: #b0b0b0; margin: 10px 0 0 0;">HTML5 | CSS3 | JavaScript</p>
                </div>
                <div style="background: #1a1a2e; padding: 15px; border-radius: 8px; text-align: center; border-left: 3px solid #00d4ff;">
                    <strong style="color: #00d4ff;">Backend</strong>
                    <p style="color: #b0b0b0; margin: 10px 0 0 0;">PHP 7+</p>
                </div>
                <div style="background: #1a1a2e; padding: 15px; border-radius: 8px; text-align: center; border-left: 3px solid #ff006e;">
                    <strong style="color: #ff006e;">Base de Données</strong>
                    <p style="color: #b0b0b0; margin: 10px 0 0 0;">PostgreSQL</p>
                </div>
                <div style="background: #1a1a2e; padding: 15px; border-radius: 8px; text-align: center; border-left: 3px solid #00d4ff;">
                    <strong style="color: #00d4ff;">Client</strong>
                    <p style="color: #b0b0b0; margin: 10px 0 0 0;">Java</p>
                </div>
            </div>
        </div>

    </div>
    </div>
</main>

<?php include 'include/footer.inc.php'; ?>