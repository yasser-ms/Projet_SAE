package client;

import java.io.*;
import java.net.Socket;

public class Client {

    public static void main(String[] args) {
        String host = "127.0.0.1";
        int port = 5000;

        try (
                Socket socket = new Socket(host, port);
                BufferedReader console = new BufferedReader(new InputStreamReader(System.in));
                BufferedReader in = new BufferedReader(new InputStreamReader(socket.getInputStream()));
                PrintWriter out = new PrintWriter(socket.getOutputStream(), true);
        ) {
            System.out.println("Connecté au serveur.");

            //  ENVOYER Borne + Parking
        
            System.out.print("ID Borne : ");
            String id_borne = console.readLine();

            System.out.print("ID Parking : ");
            String id_parking = console.readLine();

            out.println(id_borne+";"+id_parking);

            String rep1 = in.readLine();
            System.out.println("Serveur ➜ " + rep1);

            if (rep1.startsWith("ERREUR")) {
                System.out.println("Erreur détectée. Impossible d'envoyer le contrat.");
                return;
            }

    
            //  ENVOYER ID CONTRAT

            System.out.print("ID Contrat : ");
            String id_contrat = console.readLine();

            out.println(id_contrat);

            String rep2 = in.readLine();
            System.out.println("Serveur ➜ " + rep2);

        } catch (IOException e) {
            e.printStackTrace();
        }
    }
}
