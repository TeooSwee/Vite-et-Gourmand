CREATE TABLE IF NOT EXISTS role (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS utilisateur (
    utilisateur_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    telephone VARCHAR(30) NOT NULL,
    ville VARCHAR(50) NOT NULL,
    pays VARCHAR(50) NOT NULL,
    adresse_postale VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    FOREIGN KEY (role_id) REFERENCES role(role_id)
);

CREATE TABLE IF NOT EXISTS avis (
    avis_id INT AUTO_INCREMENT PRIMARY KEY,
    note VARCHAR(50) NOT NULL,
    description VARCHAR(50) NOT NULL,
    statut VARCHAR(50) NOT NULL,
    utilisateur_id INT NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id)
);

CREATE TABLE IF NOT EXISTS commande (
    numero_commande VARCHAR(50) PRIMARY KEY,
    date_commande DATE NOT NULL,
    date_prestation DATE NOT NULL,
    heure_livraison VARCHAR(50) NOT NULL,
    prix_menu DOUBLE NOT NULL,
    nombre_personne INT NOT NULL,
    prix_livraison DOUBLE NOT NULL,
    statut VARCHAR(50) NOT NULL,
    pret_materiel BOOL NOT NULL,
    restitution_materiel BOOL NOT NULL,
    utilisateur_id INT NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(utilisateur_id)
);

CREATE TABLE IF NOT EXISTS menu (
    menu_id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(50) NOT NULL,
    nombre_personne_minimum INT NOT NULL,
    prix_par_personne DOUBLE NOT NULL,
    regime VARCHAR(50) NOT NULL,
    description VARCHAR(50) NOT NULL,
    quantite_restante INT NOT NULL
);

CREATE TABLE IF NOT EXISTS regime (
    regime_id INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS theme (
    theme_id INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS plat (
    plat_id INT AUTO_INCREMENT PRIMARY KEY,
    titre_plat VARCHAR(50) NOT NULL,
    photo BLOB
);

CREATE TABLE IF NOT EXISTS allergene (
    allergene_id INT AUTO_INCREMENT PRIMARY KEY,
    libelle VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS horaire (
    horaire_id INT AUTO_INCREMENT PRIMARY KEY,
    jour VARCHAR(50) NOT NULL,
    heure_ouverture VARCHAR(50) NOT NULL,
    heure_fermeture VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS menu_theme (
    menu_id INT NOT NULL,
    theme_id INT NOT NULL,
    PRIMARY KEY (menu_id, theme_id),
    FOREIGN KEY (menu_id) REFERENCES menu(menu_id),
    FOREIGN KEY (theme_id) REFERENCES theme(theme_id)
);

CREATE TABLE IF NOT EXISTS menu_regime (
    menu_id INT NOT NULL,
    regime_id INT NOT NULL,
    PRIMARY KEY (menu_id, regime_id),
    FOREIGN KEY (menu_id) REFERENCES menu(menu_id),
    FOREIGN KEY (regime_id) REFERENCES regime(regime_id)
);

CREATE TABLE IF NOT EXISTS menu_plat (
    menu_id INT NOT NULL,
    plat_id INT NOT NULL,
    PRIMARY KEY (menu_id, plat_id),
    FOREIGN KEY (menu_id) REFERENCES menu(menu_id),
    FOREIGN KEY (plat_id) REFERENCES plat(plat_id)
);

CREATE TABLE IF NOT EXISTS plat_allergene (
    plat_id INT NOT NULL,
    allergene_id INT NOT NULL,
    PRIMARY KEY (plat_id, allergene_id),
    FOREIGN KEY (plat_id) REFERENCES plat(plat_id),
    FOREIGN KEY (allergene_id) REFERENCES allergene(allergene_id)
);

CREATE TABLE IF NOT EXISTS commande_menu (
    numero_commande VARCHAR(50) NOT NULL,
    menu_id INT NOT NULL,
    PRIMARY KEY (numero_commande, menu_id),
    FOREIGN KEY (numero_commande) REFERENCES commande(numero_commande),
    FOREIGN KEY (menu_id) REFERENCES menu(menu_id)
);

