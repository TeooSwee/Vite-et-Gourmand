
<?php
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

if (getenv('JAWSDB_URL')) {
    $url = parse_url(getenv('JAWSDB_URL'));
    $dbHote = $url['host'];
    $dbNom = ltrim($url['path'], '/');
    $dbUtilisateur = $url['user'];
    $dbMotDePasse = $url['pass'];
    $dsn = "mysql:host={$dbHote};dbname={$dbNom};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUtilisateur, $dbMotDePasse, $options);
} else {
    $dbHote = 'localhost';
    $dbNom = 'vite_gourmand';
    $dbUtilisateur = 'root';
    $dbMotDePasse = 'root';
    $pdoHote = new PDO("mysql:host={$dbHote};charset=utf8mb4", $dbUtilisateur, $dbMotDePasse, $options);
    $pdoHote->exec("CREATE DATABASE IF NOT EXISTS `{$dbNom}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $dsn = "mysql:host={$dbHote};dbname={$dbNom};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUtilisateur, $dbMotDePasse, $options);
}
$pdo->exec("CREATE TABLE IF NOT EXISTS role (role_id INT AUTO_INCREMENT PRIMARY KEY, libelle VARCHAR(50) NOT NULL)");
$pdo->exec("CREATE TABLE IF NOT EXISTS utilisateur (utilisateur_id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, nom VARCHAR(50) NOT NULL, prenom VARCHAR(50) NOT NULL, telephone VARCHAR(30) NOT NULL, ville VARCHAR(50) NOT NULL, pays VARCHAR(50) NOT NULL, adresse_postale VARCHAR(255) NOT NULL, role_id INT NOT NULL, FOREIGN KEY (role_id) REFERENCES role(role_id))");
$colNom = $pdo->query("SHOW COLUMNS FROM utilisateur LIKE 'nom'");
if ($colNom->rowCount() === 0) {
    $pdo->exec("ALTER TABLE utilisateur ADD COLUMN nom VARCHAR(50) NOT NULL DEFAULT '' AFTER password");
}
$pdo->exec("CREATE TABLE IF NOT EXISTS avis (avis_id INT AUTO_INCREMENT PRIMARY KEY, note VARCHAR(50) NOT NULL, description VARCHAR(255) NOT NULL, statut VARCHAR(50) NOT NULL, utilisateur_id INT NOT NULL, FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id))");
$pdo->exec("ALTER TABLE avis MODIFY description VARCHAR(255) NOT NULL");
$pdo->exec("CREATE TABLE IF NOT EXISTS commande (numero_commande VARCHAR(50) PRIMARY KEY, date_commande DATE NOT NULL, date_prestation DATE NOT NULL, heure_livraison VARCHAR(50) NOT NULL, prix_menu DOUBLE NOT NULL, nombre_personne INT NOT NULL, prix_livraison DOUBLE NOT NULL, statut VARCHAR(50) NOT NULL, pret_materiel BOOL NOT NULL, restitution_materiel BOOL NOT NULL, utilisateur_id INT NOT NULL, FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id))");
$pdo->exec("CREATE TABLE IF NOT EXISTS menu (menu_id INT AUTO_INCREMENT PRIMARY KEY, titre VARCHAR(50) NOT NULL, nombre_personne_minimum INT NOT NULL, prix_par_personne DOUBLE NOT NULL, regime VARCHAR(50) NOT NULL, description VARCHAR(255) NOT NULL, quantite_restante INT NOT NULL)");
$pdo->exec("ALTER TABLE menu MODIFY description VARCHAR(255) NOT NULL");
$pdo->exec("CREATE TABLE IF NOT EXISTS regime (regime_id INT AUTO_INCREMENT PRIMARY KEY, libelle VARCHAR(50) NOT NULL)");
$pdo->exec("CREATE TABLE IF NOT EXISTS theme (theme_id INT AUTO_INCREMENT PRIMARY KEY, libelle VARCHAR(50) NOT NULL)");
$pdo->exec("CREATE TABLE IF NOT EXISTS plat (plat_id INT AUTO_INCREMENT PRIMARY KEY, titre_plat VARCHAR(255) NOT NULL, photo BLOB)");
$pdo->exec("ALTER TABLE plat MODIFY titre_plat VARCHAR(255) NOT NULL");
$pdo->exec("CREATE TABLE IF NOT EXISTS allergene (allergene_id INT AUTO_INCREMENT PRIMARY KEY, libelle VARCHAR(50) NOT NULL)");
$pdo->exec("CREATE TABLE IF NOT EXISTS menu_theme (menu_id INT NOT NULL, theme_id INT NOT NULL, PRIMARY KEY (menu_id, theme_id), FOREIGN KEY (menu_id) REFERENCES menu(menu_id), FOREIGN KEY (theme_id) REFERENCES theme(theme_id))");
$pdo->exec("CREATE TABLE IF NOT EXISTS menu_regime (menu_id INT NOT NULL, regime_id INT NOT NULL, PRIMARY KEY (menu_id, regime_id), FOREIGN KEY (menu_id) REFERENCES menu(menu_id), FOREIGN KEY (regime_id) REFERENCES regime(regime_id))");
$pdo->exec("CREATE TABLE IF NOT EXISTS menu_plat (menu_id INT NOT NULL, plat_id INT NOT NULL, PRIMARY KEY (menu_id, plat_id), FOREIGN KEY (menu_id) REFERENCES menu(menu_id), FOREIGN KEY (plat_id) REFERENCES plat(plat_id))");
$pdo->exec("CREATE TABLE IF NOT EXISTS plat_allergene (plat_id INT NOT NULL, allergene_id INT NOT NULL, PRIMARY KEY (plat_id, allergene_id), FOREIGN KEY (plat_id) REFERENCES plat(plat_id), FOREIGN KEY (allergene_id) REFERENCES allergene(allergene_id))");
$pdo->exec("CREATE TABLE IF NOT EXISTS commande_menu (numero_commande VARCHAR(50) NOT NULL, menu_id INT NOT NULL, PRIMARY KEY (numero_commande, menu_id), FOREIGN KEY (numero_commande) REFERENCES commande(numero_commande), FOREIGN KEY (menu_id) REFERENCES menu(menu_id))");
$pdo->exec("CREATE TABLE IF NOT EXISTS reinitialisations_mdp (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) NOT NULL, token_hash VARCHAR(64) NOT NULL, cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX (email), INDEX (token_hash))");
$pdo->exec("INSERT IGNORE INTO role (role_id, libelle) VALUES (1, 'utilisateur')");
 $pdo->exec("INSERT IGNORE INTO role (role_id, libelle) VALUES (2, 'employe')");
$pdo->exec("INSERT IGNORE INTO role (role_id, libelle) VALUES (3, 'admin')");



$colNom = $pdo->query("SHOW COLUMNS FROM utilisateur LIKE 'nom'");

