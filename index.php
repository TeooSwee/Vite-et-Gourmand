<?php
// Affiche les erreurs PHP pour le debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirige vers la page d'accueil principale
header('Location: Accueil.php');
exit;
