/**
 * Logger utilitaire pour le client Parking.
 * Permet d'écrire des messages de log dans la console et dans un fichier,
 * avec différents niveaux (INFO, WARNING, ERROR, DEBUG) et gestion des erreurs réseau.
 */
package client;

import java.io.FileWriter;
import java.io.IOException;
import java.io.PrintWriter;
import java.text.SimpleDateFormat;
import java.util.Date;

public class Logger {
    private static final String LOG_FILE = "client.log";
    private static final SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
    private static boolean enableConsole = true;
    private static boolean enableFile = true;
    
    public enum Level {
        INFO("INFO"),
        WARNING("WARNING"),
        ERROR("ERROR"),
        DEBUG("DEBUG");
        
        private final String label;
        
        Level(String label) {
            this.label = label;
        }
        
        public String getLabel() { return label; }
    }
    
    /**
     * Écrit un message de log dans la console ET dans un fichier
     */
    public static void log(Level level, String message) {
        String timestamp = dateFormat.format(new Date());
        String logMessage = String.format("%s - [%s] - %s", timestamp, level.getLabel(), message);
        
        // Afficher dans la console
        if (enableConsole) {
            System.out.println(logMessage);
        }
        
        // Écrire dans le fichier
        if (enableFile) {
            try (PrintWriter writer = new PrintWriter(new FileWriter(LOG_FILE, true))) {
                writer.println(logMessage);
            } catch (IOException e) {
                System.err.println("Erreur d'ecriture dans le fichier de log : " + e.getMessage());
            }
        }
    }
    
    /**
     * Log de niveau INFO
     */
    public static void info(String message) {
        log(Level.INFO, message);
    }
    
    /**
     * Log de niveau WARNING
     */
    public static void warning(String message) {
        log(Level.WARNING, message);
    }
    
    /**
     * Log de niveau ERROR
     */
    public static void error(String message) {
        log(Level.ERROR, message);
    }
    
    /**
     * Log de niveau DEBUG
     */
    public static void debug(String message) {
        log(Level.DEBUG, message);
    }
    
    /**
     * Active/Désactive l'affichage console
     */
    public static void setConsoleEnabled(boolean enabled) {
        enableConsole = enabled;
    }
    
    /**
     * Active/Désactive l'écriture dans le fichier
     */
    public static void setFileEnabled(boolean enabled) {
        enableFile = enabled;
    }
    
    /**
     * Log d'une erreur réseau avec détails
     */
    public static void logNetworkError(String errorType, String details, Exception e) {
        error("===========================================");
        error("ERREUR RESEAU : " + errorType);
        error("-------------------------------------------");
        error(details);
        if (e != null) {
            error("Details technique : " + e.getMessage());
        }
        error("===========================================");
    }
}