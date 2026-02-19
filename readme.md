# 1. Installation de MAMP

- Télécharger et installer MAMP
- Lancer MAMP et démarrer les serveurs

# 2. Création du dossier de projet
- Placer le dossier du projet dans le répertoire `htdocs` de MAMP : 
`/Applications/MAMP/htdocs/Evaluations`

# 3. Configuration de la base de données
- Ouvrir phpMyAdmin
- Importer le fichier `schema.sql` pour créer la base de données

# 4. Installation des dépendances PHP
- Installer Composer
- Ouvrir un terminal dans le dossier du projet et exécuter :
  bash
  composer install

# 5. Configuration des fichiers
- Vérifier et adapter les paramètres de connexion à la base de données dans `db.php` et `config.php`

# 6. Lancement de l’application
- Accéder à l’application via l’URL : `http://localhost:8888/Evaluations/`