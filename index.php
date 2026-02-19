<?php
// Affiche les erreurs PHP pour le debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Test : inclusion de Accueil.php
echo '<b>Début index.php</b><br>';
if (file_exists('Accueil.php')) {
	include 'Accueil.php';
	echo '<br><b>Fin index.php</b>';
} else {
	echo "<b>Erreur :</b> Accueil.php introuvable";
}
exit;
