package client;   // Déclare le package du projet

import java.io.BufferedReader;          // Pour lire du texte efficacement
import java.io.IOException;             // Pour gérer les erreurs d'entrée/sortie
import java.io.InputStreamReader;       // Pour lire l'entrée en texte
import java.io.PrintWriter;             // Pour envoyer du texte au serveur
import java.net.Socket;                 // Pour créer une connexion TCP au serveur

public class Client {

    public static void main(String[] args) {
        String host = "127.0.0.1";   // Adresse du serveur
        int port = 5000;            // Port du serveur

        try (
                // Connexion TCP au serveur
                Socket socket = new Socket(host, port);

                // Pour lire ce que l’utilisateur tape dans la console
                BufferedReader console = new BufferedReader(new InputStreamReader(System.in));

                // Pour lire les réponses envoyées par le serveur
                BufferedReader in = new BufferedReader(new InputStreamReader(socket.getInputStream()));

                // Pour envoyer des messages au serveur
                PrintWriter out = new PrintWriter(socket.getOutputStream(), true);
        ) {
            System.out.println("Connecté au serveur.");   // Message indiquant que la connexion est réussie

            // ------- ENVOYER Borne + Parking -------

            System.out.print("ID Borne : ");      // Demande l'ID de la borne à l'utilisateur
            String id_borne = console.readLine(); // Récupère l'entrée utilisateur

            System.out.print("ID Parking : ");    // Demande l'ID du parking
            String id_parking = console.readLine(); // Récupère l'entrée utilisateur

            out.println(id_borne+";"+id_parking);  // Envoie les deux valeurs séparées par ";" au serveur

            String rep1 = in.readLine();           // Lit la réponse du serveur
            System.out.println("Serveur ➜ " + rep1); // Affiche la réponse du serveur

            // Si le serveur répond par "ERREUR", on arrête le processus
            if (rep1.startsWith("ERREUR")) {
                System.out.println("Erreur détectée. Impossible d'envoyer le contrat.");
                return;   // Quitte le programme
            }

            // ------- ENVOYER ID CONTRAT -------

            System.out.print("ID Contrat : ");   // Demande l'ID du contrat
            String id_contrat = console.readLine(); // Récupère l'entrée

            out.println(id_contrat);             // Envoie l'ID du contrat au serveur

            String rep2 = in.readLine();         // Lit la réponse finale du serveur
            System.out.println("Serveur ➜ " + rep2); // Affiche la réponse

        } catch (IOException e) {
            e.printStackTrace();  // Affiche une erreur si un problème d’E/S apparaît
        }
    }
}
