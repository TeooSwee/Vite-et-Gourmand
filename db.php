

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
    $dsn = "mysql:host={$dbHote};dbname={$dbNom};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUtilisateur, $dbMotDePasse, $options);
}
$pdo->exec("CREATE TABLE IF NOT EXISTS plat_allergene (plat_id INT NOT NULL, allergene_id INT NOT NULL, PRIMARY KEY (plat_id, allergene_id), FOREIGN KEY (plat_id) REFERENCES plat(plat_id), FOREIGN KEY (allergene_id) REFERENCES allergene(allergene_id))");
$pdo->exec("CREATE TABLE IF NOT EXISTS commande_menu (numero_commande VARCHAR(50) NOT NULL, menu_id INT NOT NULL, PRIMARY KEY (numero_commande, menu_id), FOREIGN KEY (numero_commande) REFERENCES commande(numero_commande), FOREIGN KEY (menu_id) REFERENCES menu(menu_id))");
$pdo->exec("CREATE TABLE IF NOT EXISTS reinitialisations_mdp (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) NOT NULL, token_hash VARCHAR(64) NOT NULL, cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX (email), INDEX (token_hash))");
$pdo->exec("INSERT IGNORE INTO role (role_id, libelle) VALUES (1, 'utilisateur')");
 $pdo->exec("INSERT IGNORE INTO role (role_id, libelle) VALUES (2, 'employe')");
$pdo->exec("INSERT IGNORE INTO role (role_id, libelle) VALUES (3, 'admin')");



$colNom = $pdo->query("SHOW COLUMNS FROM utilisateur LIKE 'nom'");

