// Client TCP pour le système de parking
// Se connecte au serveur, envoie l'ID de la borne et du parking, puis l'ID du contrat
// Reçoit les réponses et affiche les statuts. Gère les erreurs et timeouts.

package client;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.PrintWriter;
import java.net.Socket;
import java.net.ConnectException;
import java.net.SocketTimeoutException;
import java.net.UnknownHostException;
import java.net.SocketException;

public class Client {
    public static void main(String[] args) {
        Logger.info("════════════════════════════════════════");
        Logger.info("     DÉMARRAGE DU CLIENT PARKING        ");
        Logger.info("════════════════════════════════════════");
        
        try {
            BufferedReader console = new BufferedReader(new InputStreamReader(System.in));
            String host;
            int port;

            // --- GESTION DES PARAMÈTRES ---
            if (args.length >= 2) {
                host = args[0];
                Logger.info("Mode ligne de commande détecté");
                Logger.info("Arguments reçus : IP=" + host + ", PORT=" + args[1]);
                
                if (!Fonctions.validerIP(host)) {
                    Logger.error("Seule l'adresse 127.0.0.1 (localhost) est autorisée !");
                    Fonctions.afficherErreurEtQuitter("");
                }
                
                port = Fonctions.parserPort(args[1]);
                if (port == -1) {
                    System.out.println("Usage: java client.Client 127.0.0.1 <PORT>");
                    Fonctions.afficherErreurEtQuitter("");
                }
                
                Logger.info("Configuration : " + host + ":" + port);
                
            } else if (args.length == 1) {
                Logger.error("Arguments incomplets");
                System.out.println("Erreur : veuillez fournir l'IP et le port.");
                System.out.println("Usage: java client.Client 127.0.0.1 <PORT>");
                Fonctions.afficherErreurEtQuitter("");
                return;
                
            } else {
                Logger.info("Mode interactif détecté");
                host = "127.0.0.1";
                System.out.println("Connexion à localhost (127.0.0.1)");
                
                System.out.print("Port du serveur : ");
                port = Fonctions.parserPort(console.readLine());
                if (port == -1) {
                    Fonctions.afficherErreurEtQuitter("");
                }
            }

            // --- CONNEXION AU SERVEUR ---
            Logger.info("Tentative de connexion à " + host + ":" + port + "...");
            
            try (
                Socket socket = new Socket(host, port);
                BufferedReader in = new BufferedReader(new InputStreamReader(socket.getInputStream()));
                PrintWriter out = new PrintWriter(socket.getOutputStream(), true);
            ) {
                socket.setSoTimeout(1000000);
                Logger.info("Connexion établie avec succès.");
                Logger.debug("Timeout configuré : 5000ms");

                // --- SAISIE ID BORNE ---
                String id_borne = Fonctions.demanderIDBorne(console);
                if (id_borne == null) {
                    Logger.error("ID borne invalide");
                    return;
                }
                
                Fonctions.verifierConnexionOuQuitter(socket);

                // --- MENU ---
                String choix = Fonctions.afficherMenuSaisie(console);
                Fonctions.verifierConnexionOuQuitter(socket);

                String[] ids = null;

                if (choix.equals("1")) {
                    Logger.info("Mode QR Code sélectionné");
                    ids = Fonctions.lireQRCodeSmartMode(console);
                    
                } else if (choix.equals("2")) {
                    ids = Fonctions.saisirIDsManuel(console, socket);
                    
                } else {
                    Logger.error("Choix invalide : '" + choix + "'");
                    System.out.println("Choix invalide ! Veuillez entrer 1 ou 2.");
                    return;
                }

                if (ids == null) {
                    Logger.error("Impossible de récupérer les IDs");
                    return;
                }

                String id_parking = ids[0];
                String id_contrat = ids[1];
                
                Logger.info("Données collectées :");
                Logger.info("  - Borne    : " + id_borne);
                Logger.info("  - Parking  : " + id_parking);
                Logger.info("  - Contrat  : " + id_contrat);

                // --- ÉTAPE 1 ---
                Logger.info("════════════ ÉTAPE 1/2 ════════════");
                String rep1 = Fonctions.envoyerEtRecevoir(out, in, id_borne + ";" + id_parking);

                if (rep1.startsWith("ERREUR")) {
                    Logger.error("Erreur détectée : " + rep1);
                    System.out.println("Erreur détectée. Impossible d'envoyer le contrat.");
                    return;
                }
                
                Logger.info("Borne validée par le serveur.");

                // --- ÉTAPE 2 ---
                Logger.info("════════════ ÉTAPE 2/2 ════════════");
                String rep2 = Fonctions.envoyerEtRecevoir(out, in, id_contrat);
                
                if (rep2.startsWith("ERREUR")) {
                    Logger.error("Erreur lors de la validation du contrat : " + rep2);
                } else {
                    Logger.info("Transaction terminée avec succès.");
                }
                
                Logger.info("════════════════════════════════════");

            } catch (ConnectException e) {
                Logger.logNetworkError(
                    "IMPOSSIBLE DE SE CONNECTER",
                    "Le serveur n'est pas démarré sur le port " + port,
                    e
                );
                System.exit(1);

            } catch (SocketTimeoutException e) {
                Logger.logNetworkError(
                    "TIMEOUT DE CONNEXION",
                    "Le serveur ne répond pas.",
                    e
                );
                System.exit(1);
                
            } catch (UnknownHostException e) {
                Logger.logNetworkError(
                    "HÔTE INCONNU",
                    "Adresse IP invalide : " + host,
                    e
                );
                System.exit(1);
                
            } catch (SocketException e) {
                Logger.logNetworkError(
                    "ERREUR SOCKET",
                    "La connexion a été interrompue.",
                    e
                );
                System.exit(1);
            }

        } catch (IOException e) {
            Logger.logNetworkError(
                "ERREUR I/O",
                "Problème de lecture/écriture réseau",
                e
            );
            System.exit(1);
        } catch (Exception e) {
            Logger.logNetworkError(
                "ERREUR INATTENDUE",
                "Une erreur non prévue s'est produite",
                e
            );
            System.exit(1);
        } finally {
            Logger.info("════════════════════════════════════════");
            Logger.info("          FIN DU CLIENT                 ");
            Logger.info("════════════════════════════════════════\n");
        }
    }
}
