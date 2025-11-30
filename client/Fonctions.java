package client;

import java.io.BufferedReader;
import java.io.File;
import java.io.IOException;
import java.io.PrintWriter;
import java.net.Socket;
import com.google.zxing.NotFoundException;

public class Fonctions {
    
    /**
     * Valide l'adresse IP (doit être localhost)
     */
    public static boolean validerIP(String host) {
        return host.equals("127.0.0.1") || host.equalsIgnoreCase("localhost");
    }
    
    /**
     * Valide le port (1-65535)
     */
    public static boolean validerPort(int port) {
        return port >= 1 && port <= 65535;
    }
    
    /**
     * Affiche un message d'erreur et quitte
     */
    public static void afficherErreurEtQuitter(String message) {
        System.out.println("❌ " + message);
        System.out.println("🔴 Arrêt du client...");
        System.exit(1);
    }
    
    /**
     * Vérifie si le serveur est toujours connecté
     */
    public static boolean verifierConnexion(Socket socket) {
        try {
            return !socket.isClosed() && socket.isConnected();
        } catch (Exception e) {
            return false;
        }
    }
    
    /**
     * Vérifie la connexion et quitte si déconnecté
     */
    public static void verifierConnexionOuQuitter(Socket socket) {
        if (!verifierConnexion(socket)) {
            afficherErreurEtQuitter("Serveur indisponible ! Connexion perdue.");
        }
    }
    
    /**
     * Valide la longueur d'une chaîne
     */
    public static boolean validerLongueur(String data, int maxLength) {
        return data != null && data.length() <= maxLength;
    }
    
    /**
     * Lit un fichier QR code et extrait les données
     * @return String[] {id_parking, id_contrat} ou null si erreur
     */
    public static String[] lireQRCode(String cheminQRCode) {
        File qrFile = new File(cheminQRCode);
        
        if (!qrFile.exists()) {
            System.out.println("❌ Erreur : Le fichier n'existe pas !");
            return null;
        }
        
        if (!qrFile.isFile()) {
            System.out.println("❌ Erreur : Ce n'est pas un fichier valide !");
            return null;
        }
        
        try {
            String qrData = QRDecoder.decodeQRCode(qrFile);
            System.out.println("✅ QR Code lu : " + qrData);
            
            String[] donnees = qrData.split(";");
            
            if (donnees.length != 2) {
                System.out.println("❌ Format QR code invalide ! Attendu: ID_PARKING;ID_CONTRAT");
                return null;
            }
            
            String id_parking = donnees[0];
            String id_contrat = donnees[1];
            
            if (!validerLongueur(id_parking, 20) || !validerLongueur(id_contrat, 20)) {
                System.out.println("❌ Erreur : données trop longues (max 20 caractères) !");
                return null;
            }
            
            return new String[]{id_parking, id_contrat};
            
        } catch (NotFoundException e) {
            System.out.println("❌ Aucun QR code trouvé dans l'image !");
            return null;
        } catch (Exception e) {
            System.out.println("❌ Erreur lors de la lecture du QR code : " + e.getMessage());
            return null;
        }
    }
    
    /**
     * Saisie manuelle des IDs avec validation
     * @return String[] {id_parking, id_contrat} ou null si erreur
     */
    public static String[] saisirIDsManuel(BufferedReader console, Socket socket) {
        try {
            System.out.print("ID Parking : ");
            String id_parking = console.readLine();
            
            verifierConnexionOuQuitter(socket);
            
            System.out.print("ID Contrat : ");
            String id_contrat = console.readLine();
            
            verifierConnexionOuQuitter(socket);
            
            if (!validerLongueur(id_parking, 20) || !validerLongueur(id_contrat, 20)) {
                System.out.println("❌ Erreur : données trop longues (max 20 caractères) !");
                return null;
            }
            
            return new String[]{id_parking, id_contrat};
            
        } catch (IOException e) {
            System.out.println("❌ Erreur de saisie : " + e.getMessage());
            return null;
        }
    }
    
    /**
     * Envoie des données au serveur et retourne la réponse
     */
    public static String envoyerEtRecevoir(PrintWriter out, BufferedReader in, String message) {
        System.out.println("\n📤 Envoi des données au serveur...");
        out.println(message);
        
        if (out.checkError()) {
            afficherErreurEtQuitter("Serveur indisponible ! Impossible d'envoyer les données.");
        }
        
        try {
            String reponse = in.readLine();
            
            if (reponse == null) {
                afficherErreurEtQuitter("Serveur indisponible ! Aucune réponse reçue.");
            }
            
            System.out.println("📥 Serveur ➜ " + reponse);
            return reponse;
            
        } catch (IOException e) {
            afficherErreurEtQuitter("Serveur indisponible ! Connexion perdue.");
            return null; // Jamais atteint, mais nécessaire pour le compilateur
        }
    }
    
    /**
     * Affiche le menu de saisie et retourne le choix
     */
    public static String afficherMenuSaisie(BufferedReader console) throws IOException {
        System.out.println("\n=== MODE DE SAISIE ===");
        System.out.println("1. Scanner un QR Code");
        System.out.println("2. Saisir manuellement les IDs");
        System.out.print("Votre choix (1 ou 2) : ");
        return console.readLine();
    }
    
    /**
     * Demande et valide l'ID de la borne
     */
    public static String demanderIDBorne(BufferedReader console) throws IOException {
        System.out.print("ID de la borne : ");
        String id_borne = console.readLine();
        
        if (!validerLongueur(id_borne, 20)) {
            System.out.println("❌ Erreur : ID borne trop long (max 20 caractères) !");
            return null;
        }
        
        return id_borne;
    }
    
    /**
     * Parse un port depuis une chaîne avec validation
     */
    public static int parserPort(String portStr) {
        try {
            int port = Integer.parseInt(portStr);
            
            if (!validerPort(port)) {
                System.out.println("❌ Erreur : Le port doit être entre 1 et 65535 !");
                return -1;
            }
            
            return port;
            
        } catch (NumberFormatException e) {
            System.out.println("❌ Erreur : Le port doit être un nombre valide !");
            return -1;
        }
    }
}