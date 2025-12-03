/**
 * Fonctions utilitaires pour le client Parking.
 * Contient la validation IP/port, la saisie d'IDs, la communication avec le serveur,
 * et la gestion des erreurs et menus interactifs.
 */
package client;

import java.io.BufferedReader;
import java.io.File;
import java.io.IOException;
import java.io.PrintWriter;
import java.net.Socket;
import java.awt.Toolkit;
import java.awt.datatransfer.Clipboard;
import java.awt.datatransfer.DataFlavor;
import java.awt.datatransfer.Transferable;
import java.util.List;
import com.google.zxing.NotFoundException;

public class Fonctions {
    
    /**
     * Valide l'adresse IP (doit être localhost)
     */
    public static boolean validerIP(String host) {
        boolean valide = host.equals("127.0.0.1") || host.equalsIgnoreCase("localhost");
        return valide;
    }
    
    /**
     * Valide le port (1-65535)
     */
    public static boolean validerPort(int port) {
        boolean valide = port >= 1 && port <= 65535;
        return valide;
    }
    
    /**
     * Affiche un message d'erreur et quitte
     */
    public static void afficherErreurEtQuitter(String message) {
        if (!message.isEmpty()) {
            Logger.error(message);
        }
        Logger.info("Arret du client...");
        System.exit(1);
    }
    
    /**
     * Vérifie si le serveur est connecté
     */
    public static boolean verifierConnexion(Socket socket) {
        try {
            boolean connecte = !socket.isClosed() && socket.isConnected();
            if (!connecte) {
                Logger.warning("Verification connexion : Socket ferme ou deconnecte");
            }
            return connecte;
        } catch (Exception e) {
            Logger.error("Erreur lors de la verification de connexion : " + e.getMessage());
            return false;
        }
    }
    
    /**
     * Vérifie la connexion et quitte si nécessaire
     */
    public static void verifierConnexionOuQuitter(Socket socket) {
        if (!verifierConnexion(socket)) {
            Logger.logNetworkError(
                "CONNEXION PERDUE",
                "Le serveur a ferme la connexion.\n" +
                "Verifiez que le serveur est actif.",
                null
            );
            afficherErreurEtQuitter("");
        }
    }
    
    /**
     * Valide la longueur d'une chaîne
     */
    public static boolean validerLongueur(String data, int maxLength) {
        if (data == null) {
            Logger.warning("Validation longueur : donnee null");
            return false;
        }
        boolean valide = data.length() <= maxLength;
        if (!valide) {
            Logger.warning("Validation longueur : " + data.length() + " caracteres (max " + maxLength + ")");
        }
        return valide;
    }
    
    /**
     * Lecture depuis le presse-papiers
     */
    public static String lireCheminDepuisPressePapiers() {
        try {
            Clipboard clipboard = Toolkit.getDefaultToolkit().getSystemClipboard();
            Transferable contents = clipboard.getContents(null);
            
            if (contents != null && contents.isDataFlavorSupported(DataFlavor.javaFileListFlavor)) {
                @SuppressWarnings("unchecked")
                List<File> files = (List<File>) contents.getTransferData(DataFlavor.javaFileListFlavor);
                
                if (!files.isEmpty()) {
                    String chemin = files.get(0).getAbsolutePath();
                    Logger.debug("Fichier detecte depuis presse-papiers (liste) : " + chemin);
                    return chemin;
                }
            }
            
            if (contents != null && contents.isDataFlavorSupported(DataFlavor.stringFlavor)) {
                String chemin = (String) contents.getTransferData(DataFlavor.stringFlavor);
                chemin = chemin.trim()
                               .replaceAll("^file://", "")
                               .replaceAll("^'|'$", "")
                               .replaceAll("^\"|\"$", "");
                
                if (new File(chemin).exists()) {
                    Logger.debug("Fichier detecte depuis presse-papiers (texte) : " + chemin);
                    return chemin;
                }
            }
            
            Logger.debug("Aucun fichier dans le presse-papiers");
            
        } catch (Exception e) {
            Logger.debug("Erreur lecture presse-papiers : " + e.getMessage());
        }
        return null;
    }
    
    /**
     * Nettoyage d'un chemin
     */
    private static String nettoyerChemin(String chemin) {
        if (chemin == null) return "";
        
        String original = chemin;
        chemin = chemin.replaceAll("^'|'$", "")
                       .replaceAll("^\"|\"$", "")
                       .replaceAll("^file://", "")
                       .trim();
        
        if (!original.equals(chemin)) {
            Logger.debug("Chemin nettoye : '" + original + "' -> '" + chemin + "'");
        }
        
        return chemin;
    }
    
    /**
     * Lecture d'un QR Code
     */
    public static String[] lireQRCode(String cheminQRCode) {
        Logger.info("Lecture du QR Code : " + cheminQRCode);
        
        File qrFile = new File(cheminQRCode);
        
        if (!qrFile.exists()) {
            Logger.error("Le fichier n'existe pas : " + cheminQRCode);
            return null;
        }
        
        if (!qrFile.isFile()) {
            Logger.error("Ce n'est pas un fichier valide : " + cheminQRCode);
            return null;
        }
        
        try {
            String qrData = QRDecoder.decodeQRCode(qrFile);
            Logger.info("QR Code decode avec succes : " + qrData);
            
            String[] donnees = qrData.split(";");
            
            if (donnees.length != 2) {
                Logger.error("Format QR code invalide ! Attendu: ID_PARKING;ID_CONTRAT, recu: " + qrData);
                return null;
            }
            
            String id_parking = donnees[0];
            String id_contrat = donnees[1];
            
            Logger.debug("ID Parking extrait : " + id_parking);
            Logger.debug("ID Contrat extrait : " + id_contrat);
            
            if (!validerLongueur(id_parking, 20) || !validerLongueur(id_contrat, 20)) {
                Logger.error("Donnees extraites trop longues (max 20 caracteres)");
                return null;
            }
            
            return new String[]{id_parking, id_contrat};
            
        } catch (NotFoundException e) {
            Logger.error("Aucun QR code trouve dans l'image : " + cheminQRCode);
            return null;
        } catch (Exception e) {
            Logger.error("Erreur lors de la lecture du QR code : " + e.getMessage());
            return null;
        }
    }
    
    /**
     * Lecture QR intelligent (drag & drop ou presse-papiers)
     */
    public static String[] lireQRCodeSmartMode(BufferedReader console) {
        System.out.print("\nGlissez-deposez le fichier QR ici : ");
        
        try {
            String input = console.readLine().trim();
            String cheminQRCode = null;
            
            if (!input.isEmpty()) {
                cheminQRCode = input;
                Logger.debug("Chemin recu via saisie : " + cheminQRCode);
            } else {
                cheminQRCode = lireCheminDepuisPressePapiers();
            }
            
            if (cheminQRCode == null || cheminQRCode.isEmpty()) {
                Logger.error("Aucun fichier detecte ! Assurez-vous d'avoir glisse ou copie un fichier.");
                return null;
            }
            
            cheminQRCode = nettoyerChemin(cheminQRCode);
            
            System.out.println("Fichier detecte : " + cheminQRCode);
            
            return lireQRCode(cheminQRCode);
            
        } catch (IOException e) {
            Logger.error("Erreur de lecture console : " + e.getMessage());
            return null;
        }
    }
    
    /**
     * Saisie manuelle
     */
    public static String[] saisirIDsManuel(BufferedReader console, Socket socket) {
        Logger.info("Mode saisie manuelle selectionne");
        
        try {
            System.out.print("ID Parking : ");
            String id_parking = console.readLine();
            Logger.debug("ID Parking saisi : " + id_parking);
            
            verifierConnexionOuQuitter(socket);
            
            System.out.print("ID Contrat : ");
            String id_contrat = console.readLine();
            Logger.debug("ID Contrat saisi : " + id_contrat);
            
            verifierConnexionOuQuitter(socket);
            
            if (!validerLongueur(id_parking, 20) || !validerLongueur(id_contrat, 20)) {
                Logger.error("Donnees trop longues (max 20 caracteres)");
                return null;
            }
            
            return new String[]{id_parking, id_contrat};
            
        } catch (IOException e) {
            Logger.error("Erreur de saisie : " + e.getMessage());
            return null;
        }
    }
    
    /**
     * Envoi et réception
     */
    public static String envoyerEtRecevoir(PrintWriter out, BufferedReader in, String message) {
        Logger.info("Envoi au serveur : " + message);
        out.println(message);
        
        if (out.checkError()) {
            Logger.logNetworkError(
                "ENVOI IMPOSSIBLE",
                "Impossible d'envoyer les donnees.\n" +
                "La connexion a ete perdue.",
                null
            );
            afficherErreurEtQuitter("");
        }
        
        try {
            String reponse = in.readLine();
            
            if (reponse == null) {
                Logger.logNetworkError(
                    "AUCUNE REPONSE",
                    "Le serveur n'a pas repondu.\n" +
                    "La connexion a ete fermee.",
                    null
                );
                afficherErreurEtQuitter("");
            }
            
            Logger.info("Reponse du serveur : " + reponse);
            return reponse;
            
        } catch (IOException e) {
            Logger.logNetworkError(
                "ERREUR DE RECEPTION",
                "Impossible de lire la reponse du serveur.\n" +
                "La connexion a ete interrompue.",
                e
            );
            afficherErreurEtQuitter("");
            return null;
        }
    }
    
    /**
     * Affiche le menu
     */
    public static String afficherMenuSaisie(BufferedReader console) throws IOException {
        System.out.println("\n=== MODE DE SAISIE ===");
        System.out.println("1. Scanner un QR Code");
        System.out.println("2. Saisir manuellement les IDs");
        System.out.print("Votre choix (1 ou 2) : ");
        String choix = console.readLine();
        Logger.debug("Choix utilisateur : " + choix);
        return choix;
    }
    
    /**
     * Demande l'ID de la borne
     */
    public static String demanderIDBorne(BufferedReader console) throws IOException {
        System.out.print("ID de la borne : ");
        String id_borne = console.readLine();
        Logger.debug("ID Borne saisi : " + id_borne);
        
        if (!validerLongueur(id_borne, 20)) {
            Logger.error("ID borne trop long (max 20 caracteres)");
            return null;
        }
        
        return id_borne;
    }
    
    /**
     * Parse un port
     */
    public static int parserPort(String portStr) {
        try {
            int port = Integer.parseInt(portStr);
            
            if (!validerPort(port)) {
                Logger.error("Port invalide : " + port + " (doit etre entre 1 et 65535)");
                return -1;
            }
            
            return port;
            
        } catch (NumberFormatException e) {
            Logger.error("Port invalide : '" + portStr + "' n'est pas un nombre");
            return -1;
        }
    }
}
