<?php
/* logout.php
   Gère le processus de déconnexion des utilisateurs.
*/
session_start();
session_destroy();
header("Location: index.php");
exit();
?>