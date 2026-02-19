<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require 'db.php';
$avis = $pdo->query("SELECT a.note, a.description, u.prenom, u.nom FROM avis a JOIN utilisateur u ON u.utilisateur_id = a.utilisateur_id WHERE a.statut = 'validé' ORDER BY a.avis_id DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vite et Gourmand - Accueil</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav>
        <ul>
            <li><a href="Accueil.php">Accueil</a></li>
            <li><a href="Menus.php">Menus</a></li>
            <li><a href="espace_utilisateur.php">Mon compte</a></li>
            <li><a href="Contact.php">Contact</a></li>
        </ul>
    </nav>

    <header>
        <h1>Bienvenue chez Vite et Gourmand</h1>
        <p>Votre partenaire pour des repas festifs et savoureux, livrés directement chez vous.</p>
    </header>

    <section id="presentation-services">
        <h2>Nos Services</h2>
        <p>Nous sommes une équipe de deux passionnés qui proposons des menus classiques adaptés aux occasions spéciales. <br>
            Que ce soit pour Noël, Pâques ou tout autre événement, nous préparons des plats traditionnels avec soin.</p>
        <p>Tous nos menus sont livrés directement à votre domicile, pour que vous puissiez profiter pleinement de vos moments en famille sans vous soucier de la cuisine.</p>
        <div class="frise">
            <div class="frise-element">
                <div class="frise-contenu">
                    <h3>Menus concoctés avec goûts</h3>
                    <p>Des plats savoureux préparés avec passion.</p>
                </div>
            </div>
            <div class="frise-element">
                <div class="frise-contenu">
                    <h3>Ingrédients frais et de qualité</h3>
                    <p>Nous sélectionnons les meilleurs ingrédients pour votre satisfaction.</p>
                </div>
            </div>
            <div class="frise-element">
                <div class="frise-contenu">
                    <h3>Livraison ponctuelle et soignée</h3>
                    <p>Vos repas arrivent à l'heure, prêts à être dégustés.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="equipe">
        <h2>Notre Équipe</h2>
        <p>Vite et Gourmand est une entreprise familiale établie à Bordeaux depuis 25 ans. <br>
             Nous sommes fiers de notre héritage et de notre engagement envers la qualité.</p> <br>
        <div class="menu">
            <div class="element carte carte-equipe">
                <img src="Images/Julie.jpg" alt="Julie">
                <h3>Julie</h3>
                <p>Co-fondatrice et chef cuisinière, Julie apporte sa créativité et son expertise culinaire pour élaborer des menus inoubliables.</p>
            </div>
            <div class="element carte carte-equipe">
                <img src="Images/José.jpg" alt="José">
                <h3>José</h3>
                <p>Co-fondateur et responsable des opérations, José veille à ce que chaque livraison soit parfaite et que nos clients soient satisfaits.</p>
            </div>
        </div>
    </section>

    <section id="avis">
        <h2>Avis Clients</h2>
        <div class="menu">
            <?php if (empty($avis)): ?>
                <div>
                    <p>Aucun avis pour le moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($avis as $ligne): ?>
                    <div>
                        <p>"<?php echo htmlspecialchars($ligne['description']); ?>"</p>
                        <em><?php echo htmlspecialchars(trim(($ligne['prenom'] ?? '') . ' ' . ($ligne['nom'] ?? ''))); ?></em>
                        <cite> <?php echo str_repeat('★', (int) $ligne['note']); ?></cite>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <div class="contenu-footer">
            <div class="horaires">
                <h3>Horaires</h3>
                <p>Lundi - Vendredi : 10h - 14h et 17h - 21h </p>
                <p>Samedi : 9h30 - 14h30 et 16h30 - 21h30</p>
                <p>Dimanche : Fermé</p>
            </div>
            <div class="liens">
                <a href="MentionsLegales.html">Mentions Légales</a>
                <a href="CGV.html">Conditions Générales de Vente</a>
            </div>
        </div>
    </footer>
</html>
