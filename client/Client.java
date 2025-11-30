package client;

import java.io.BufferedReader;
import java.io.File;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.PrintWriter;
import java.net.Socket;
import java.net.ConnectException;
import java.net.SocketTimeoutException;

import com.google.zxing.NotFoundException;

public class Client {
    public static void main(String[] args) {
        try {
            BufferedReader console = new BufferedReader(new InputStreamReader(System.in));

            String host;
            int port;

            // --- Vérifier si IP et port sont passés en paramètres ---
            if (args.length >= 2) {
                // Mode paramètre : java Client <IP> <PORT>
                host = args[0];
                
                // Vérifier que l'IP est bien localhost
                if (!host.equals("127.0.0.1") && !host.equalsIgnoreCase("localhost")) {
                    System.out.println(" Erreur : Seule l'adresse 127.0.0.1 (localhost) est autorisée !");
                    System.out.println("Usage: java client.Client 127.0.0.1 <PORT>");
                    System.out.println(" Arrêt du client...");
                    System.exit(1);
                    return;
                }
                
                try {
                    port = Integer.parseInt(args[1]);
                    
                    // Vérifier que le port est dans la plage valide
                    if (port < 1 || port > 65535) {
                        System.out.println(" Erreur : Le port doit être entre 1 et 65535 !");
                        System.out.println("Usage: java client.Client 127.0.0.1 <PORT>");
                        System.out.println(" Arrêt du client...");
                        System.exit(1);
                        return;
                    }
                    
                    System.out.println(" IP spécifiée : " + host);
                    System.out.println(" Port spécifié : " + port);
                    
                } catch (NumberFormatException e) {
                    System.out.println(" Erreur : Le port doit être un nombre valide !");
                    System.out.println("Usage: java client.Client 127.0.0.1 <PORT>");
                    System.out.println(" Arrêt du client...");
                    System.exit(1);
                    return;
                }
                
            } else if (args.length == 1) {
                // Si seulement un paramètre est fourni
                System.out.println(" Erreur : Veuillez fournir à la fois l'IP et le port !");
                System.out.println("Usage: java client.Client 127.0.0.1 <PORT>");
                System.out.println(" Arrêt du client...");
                System.exit(1);
                return;
                
            } else {
                // Mode interactif : aucun paramètre
                host = "127.0.0.1";
                System.out.println(" Connexion à localhost (127.0.0.1)");
                
                System.out.print("Port du serveur : ");
                try {
                    port = Integer.parseInt(console.readLine());
                    
                    // Vérifier que le port est dans la plage valide
                    if (port < 1 || port > 65535) {
                        System.out.println("Erreur : Le port doit être entre 1 et 65535 !");
                        System.out.println(" Arrêt du client...");
                        System.exit(1);
                        return;
                    }
                } catch (NumberFormatException e) {
                    System.out.println("Erreur : Le port doit être un nombre valide !");
                    System.out.println("Arrêt du client...");
                    System.exit(0);
                    return;
                }
            }

            // --- Tentative de connexion au serveur ---
            try (
                    Socket socket = new Socket(host, port);
                    BufferedReader in = new BufferedReader(new InputStreamReader(socket.getInputStream()));
                    PrintWriter out = new PrintWriter(socket.getOutputStream(), true);
            ) {

                socket.setSoTimeout(5000);

                System.out.println("Connecté au serveur.");

                // --- VÉRIFICATION PERMANENTE DE LA CONNEXION ---
               /* try {
                    out.println("PING");
                    if (out.checkError()) {
                        System.out.println(" Serveur indisponible ! Connexion perdue.");
                        System.out.println(" Arrêt du client...");
                        System.exit(0);
                    }
                } catch (Exception e) {
                    System.out.println("Serveur indisponible ! Connexion perdue.");
                    System.out.println(" Arrêt du client...");
                    System.exit(0);
                }*/ 

                // --- SAISIE DE L'ID BORNE AU CLAVIER ---
                System.out.print("ID de la borne : ");
                String id_borne = console.readLine();

                // Vérifier que le serveur est toujours connecté après la saisie
                try {
                    if (socket.isClosed() || !socket.isConnected()) {
                        System.out.println("Serveur indisponible ! Connexion perdue.");
                        System.out.println("Arrêt du client...");
                        System.exit(0);
                    }
                } catch (Exception e) {
                    System.out.println("Serveur indisponible ! Connexion perdue.");
                    System.out.println("Arrêt du client...");
                    System.exit(0);
                }

                // Vérification de la longueur
                if (id_borne.length() > 20) {
                    System.out.println("Erreur : ID borne trop long (max 20 caractères) !");
                    return;
                }

                // --- MENU : QR CODE OU SAISIE MANUELLE ---
                System.out.println("\n=== MODE DE SAISIE ===");
                System.out.println("1. Scanner un QR Code");
                System.out.println("2. Saisir manuellement les IDs");
                System.out.print("Votre choix (1 ou 2) : ");
                String choix = console.readLine();

                // Vérifier que le serveur est toujours connecté après le choix
                try {
                    if (socket.isClosed() || !socket.isConnected()) {
                        System.out.println("Serveur indisponible ! Connexion perdue.");
                        System.out.println("Arrêt du client...");
                        System.exit(0);
                    }
                } catch (Exception e) {
                    System.out.println("Serveur indisponible ! Connexion perdue.");
                    System.out.println("Arrêt du client...");
                    System.exit(0);
                }

                String id_parking = "";
                String id_contrat = "";

                if (choix.equals("1")) {
                    // --- MODE QR CODE ---
                    System.out.print("Chemin du fichier QR code : ");
                    String cheminQRCode = console.readLine();

                    File qrFile = new File(cheminQRCode);
                    String qrData = "";

                    if (!qrFile.exists()) {
                        System.out.println("Erreur : Le fichier n'existe pas !");
                        return;
                    }

                    if (!qrFile.isFile()) {
                        System.out.println("Erreur : Ce n'est pas un fichier valide !");
                        return;
                    }

                    try {
                        qrData = QRDecoder.decodeQRCode(qrFile);
                        System.out.println("QR Code lu : " + qrData);
                    } catch (NotFoundException e) {
                        System.out.println("Aucun QR code trouvé dans l'image !");
                        return;
                    }

                    String[] donnees = qrData.split(";");

                    if (donnees.length != 2) {
                        System.out.println("Format QR code invalide ! Attendu: ID_PARKING;ID_CONTRAT");
                        return;
                    }

                    id_parking = donnees[0];
                    id_contrat = donnees[1];

                    if (id_parking.length() > 20 || id_contrat.length() > 20) {
                        System.out.println("Erreur : données trop longues !");
                        return;
                    }

                } else if (choix.equals("2")) {
                    // --- MODE SAISIE MANUELLE ---
                    System.out.print("ID Parking : ");
                    id_parking = console.readLine();

                    try {
                        if (socket.isClosed() || !socket.isConnected()) {
                            System.out.println("Serveur indisponible ! Connexion perdue.");
                            System.out.println("Arrêt du client...");
                            System.exit(0);
                        }
                    } catch (Exception e) {
                        System.out.println("Serveur indisponible ! Connexion perdue.");
                        System.out.println("Arrêt du client...");
                        System.exit(0);
                    }

                    System.out.print("ID Contrat : ");
                    id_contrat = console.readLine();

                    try {
                        if (socket.isClosed() || !socket.isConnected()) {
                            System.out.println("Serveur indisponible ! Connexion perdue.");
                            System.out.println("Arrêt du client...");
                            System.exit(0);
                        }
                    } catch (Exception e) {
                        System.out.println("Serveur indisponible ! Connexion perdue.");
                        System.out.println("Arrêt du client...");
                        System.exit(0);
                    }

                    if (id_parking.length() > 20 || id_contrat.length() > 20) {
                        System.out.println("Erreur : données trop longues (max 20 caractères) !");
                        return;
                    }

                } else {
                    System.out.println("Choix invalide ! Veuillez entrer 1 ou 2.");
                    return;
                }

               // System.out.println("ID Borne saisie : " + id_borne);
               // System.out.println("ID Parking extraite : " + id_parking);
               // System.out.println("ID Contrat extraite : " + id_contrat);

                // --- ENVOI borne + parking au serveur ---
                System.out.println("\n Envoi des données au serveur...");
                out.println(id_borne + ";" + id_parking);

                if (out.checkError()) {
                    System.out.println("Serveur indisponible ! Impossible d'envoyer les données.");
                    System.out.println("Arrêt du client...");
                    System.exit(0);
                }

                String rep1 = null;
                try {
                    rep1 = in.readLine();
                } catch (IOException e) {
                    System.out.println(" Serveur indisponible ! Connexion perdue.");
                    System.out.println(" Arrêt du client...");
                    System.exit(0);
                }

                if (rep1 == null) {
                    System.out.println("Serveur indisponible ! Aucune réponse reçue.");
                    System.out.println("Arrêt du client...");
                    System.exit(0);
                }

                System.out.println("Serveur ➜ " + rep1);

                if (rep1.startsWith("ERREUR")) {
                    System.out.println("Erreur détectée. Impossible d'envoyer le contrat.");
                    return;
                }

                // --- ENVOI de l'ID contrat ---
                System.out.println("\n Envoi de l'ID Contrat au serveur...");
                out.println(id_contrat);

                if (out.checkError()) {
                    System.out.println(" Serveur indisponible ! Impossible d'envoyer le contrat.");
                    System.out.println(" Arrêt du client...");
                    System.exit(0);
                }

                String rep2 = null;
                try {
                    rep2 = in.readLine();
                } catch (IOException e) {
                    System.out.println(" Serveur indisponible ! Connexion perdue.");
                    System.out.println(" Arrêt du client...");
                    System.exit(0);
                }

                if (rep2 == null) {
                    System.out.println("Serveur indisponible ! Aucune réponse reçue.");
                    System.out.println("Arrêt du client...");
                    System.exit(0);
                }

                System.out.println("Serveur ➜ " + rep2);

            } catch (ConnectException e) {
                System.out.println("Impossible de se connecter : port fermé ou serveur inaccessible.");
                System.out.println("Arrêt du client...");
                System.exit(0);

            } catch (SocketTimeoutException e) {
                System.out.println("Le serveur met trop de temps à répondre !");
                System.out.println("Arrêt du client...");
                System.exit(0);
            }

        } catch (IOException e) {
            System.out.println("Erreur : " + e.getMessage());
            System.out.println("Arrêt du client...");
            System.exit(0);
        } catch (Exception e) {
            System.out.println("Erreur inattendue : " + e.getMessage());
            System.out.println("rrêt du client...");
            System.exit(0);
        }
    }
}