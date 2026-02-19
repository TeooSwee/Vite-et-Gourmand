<?php
include 'db.php';
$menus = $pdo->query(
    "SELECT m.*, 
            GROUP_CONCAT(DISTINCT t.libelle SEPARATOR '|') AS themes,
            GROUP_CONCAT(DISTINCT r.libelle SEPARATOR '|') AS regimes
     FROM menu m
     LEFT JOIN menu_theme mt ON mt.menu_id = m.menu_id
     LEFT JOIN theme t ON t.theme_id = mt.theme_id
     LEFT JOIN menu_regime mr ON mr.menu_id = m.menu_id
     LEFT JOIN regime r ON r.regime_id = mr.regime_id
     GROUP BY m.menu_id
     ORDER BY m.menu_id"
)->fetchAll(PDO::FETCH_ASSOC);
$platsStmt = $pdo->prepare(
    "SELECT p.plat_id, p.titre_plat,
            GROUP_CONCAT(DISTINCT a.libelle SEPARATOR ', ') AS allergenes
     FROM menu_plat mp
     JOIN plat p ON p.plat_id = mp.plat_id
     LEFT JOIN plat_allergene pa ON pa.plat_id = p.plat_id
     LEFT JOIN allergene a ON a.allergene_id = pa.allergene_id
     WHERE mp.menu_id = :id
     GROUP BY p.plat_id"
);
$images = [
    'Menu Noël' => 'Images/noel.jpg',
    'Menu Pâques' => 'Images/paques.jpg',
    'Menu de saison - Hiver' => 'Images/saison.jpg',
    'Menu Anniversaire' => 'Images/anniversaire.jpg',
    'Menu Découverte' => 'Images/decouverte.jpg'
];
function normaliserListe($valeur): string
{
    $valeur = trim($valeur);
    if ($valeur === '') {
        return '';
    }
    $valeur = str_replace([', ', ','], '|', $valeur);
    return $valeur;
}
function textePourDeux(array $elements): string
{
    $elements = array_values(array_filter($elements, fn($v) => trim($v) !== ''));
    if (count($elements) === 0) {
        return '—|—';
    }
    if (count($elements) === 1) {
        return $elements[0] . '|—';
    }
    return $elements[0] . '|' . $elements[1];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vite et Gourmand - Nos menus</title>
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

    <h2>Nos menus</h2>
    <div class="filtres-menus">
        <div class="groupe-filtre">
            <label for="filtre-prix">Prix :</label>
            <select id="filtre-prix">
                <option value="tout">Tous les prix</option>
                <option value="0-30">0 € - 30 €</option>
                <option value="31-40">31 € - 40 €</option>
                <option value="41-50">41 € - 50 €</option>
            </select>
        </div>
        <div class="groupe-filtre">
            <label for="filtre-prix-max">Prix maximum :</label>
            <select id="filtre-prix-max">
                <option value="tout">Aucun</option>
                <option value="20">20 €</option>
                <option value="30">30 €</option>
                <option value="40">40 €</option>
                <option value="50">50 €</option>
            </select>
        </div>
        <div class="groupe-filtre">
            <label for="filtre-regime">Régime :</label>
            <select id="filtre-regime">
                <option value="tout">Tous les régimes</option>
                <option value="Classique">Classique</option>
                <option value="Végétarien">Végétarien</option>
                <option value="Végan">Végan</option>
            </select>
        </div>
        <div class="groupe-filtre">
            <label for="filtre-theme">Thème :</label>
            <select id="filtre-theme">
                <option value="tout">Tous</option>
                <option value="saisonnier">Saisonnier</option>
                <option value="evenements">Événements</option>
            </select>
        </div>
        <div class="groupe-filtre">
            <label for="filtre-minimum">Nombre minimum de personnes :</label>
            <select id="filtre-minimum">
                <option value="tout">Tous</option>
                <option value="2">2 personnes</option>
                <option value="4">4 personnes</option>
            </select>
        </div>
    </div>

    <div class="menu" id="liste-menus">
        <?php foreach ($menus as $menu): ?>
            <?php
                $platsStmt->execute([':id' => $menu['menu_id']]);
                $plats = $platsStmt->fetchAll(PDO::FETCH_ASSOC);

                $titres = array_map(fn($p) => $p['titre_plat'], $plats);
                $entrees = array_slice($titres, 0, 2);
                $platsPrincipaux = array_slice($titres, 2, 2);
                $desserts = array_slice($titres, 4, 2);

                $allergenesParPlat = [];
                foreach ($plats as $plat) {
                    $allergenesParPlat[$plat['titre_plat']] = $plat['allergenes'] ?: '—';
                }
                $allergenesPlats = array_map(fn($titre) => $allergenesParPlat[$titre] ?? '—', $platsPrincipaux);

                $regimes = $menu['regimes'] ?: $menu['regime'];
                $regimes = normaliserListe((string) $regimes);
                $themes = normaliserListe((string) ($menu['themes'] ?? ''));
                $image = $images[$menu['titre']] ?? 'Images/noel.jpg';
            ?>
            <div class="element carte carte-menu"
                data-prix="<?php echo htmlspecialchars($menu['prix_par_personne']); ?>"
                data-theme="<?php echo htmlspecialchars($themes); ?>"
                data-regime="<?php echo htmlspecialchars($regimes); ?>"
                data-entrees="<?php echo htmlspecialchars(textePourDeux($entrees)); ?>"
                data-plats="<?php echo htmlspecialchars(textePourDeux($platsPrincipaux)); ?>"
                data-desserts="<?php echo htmlspecialchars(textePourDeux($desserts)); ?>"
                data-allergenes-plats="<?php echo htmlspecialchars(textePourDeux($allergenesPlats)); ?>"
                data-stock="<?php echo (int)$menu['quantite_restante']; ?>">
                <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($menu['titre']); ?>">
                <h3><?php echo htmlspecialchars($menu['titre']); ?></h3>
                <p><?php echo htmlspecialchars($menu['description']); ?></p>
                <p>Prix : <?php echo htmlspecialchars($menu['prix_par_personne']); ?>€ par personne</p>
                <p class="menu-minimum">Minimum : <?php echo htmlspecialchars($menu['nombre_personne_minimum']); ?> personnes</p>
                <div class="actions-menu">
                    <button type="button" class="bouton-voir-menu">Voir le détail</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="fenetre-menu" id="fenetre-menu" aria-hidden="true">
        <div class="fenetre-menu-contenu" role="dialog" aria-modal="true" aria-labelledby="fenetre-menu-titre">
            <button type="button" class="fenetre-menu-fermer" id="fenetre-menu-fermer" aria-label="Fermer">×</button>
            <h3 id="fenetre-menu-titre"></h3>
            <p class="fenetre-menu-aide">Choisissez un menu complet : choix 1 ou choix 2 sous limite de stock.<br>
            Nécessite de passer commande minimum 3 jours avant.</p>
            <div id="fenetre-menu-stock" class="fenetre-menu-stock"></div>
            <div class="fenetre-menu-colonnes">
                <div class="fenetre-menu-colonne" id="fenetre-colonne-1">
                    <h4>Choix 1</h4>
                    <p class="fenetre-regime" id="fenetre-regime-1"></p>
                    <div class="fenetre-menu-categorie">
                        <h5>Entrée</h5>
                        <p id="fenetre-entree-1"></p>
                    </div>
                    <div class="fenetre-menu-categorie">
                        <h5>Plat</h5>
                        <p id="fenetre-plat-1"></p>
                        <p class="fenetre-allergenes" id="fenetre-allergenes-plat-1"></p>
                    </div>
                    <div class="fenetre-menu-categorie">
                        <h5>Dessert</h5>
                        <p id="fenetre-dessert-1"></p>
                    </div>
                    <button type="button" class="fenetre-choix" id="fenetre-choix-1">Choisir ce menu</button>
                </div>
                <div class="fenetre-menu-colonne" id="fenetre-colonne-2">
                    <h4>Choix 2</h4>
                    <p class="fenetre-regime" id="fenetre-regime-2"></p>
                    <div class="fenetre-menu-categorie">
                        <h5>Entrée</h5>
                        <p id="fenetre-entree-2"></p>
                    </div>
                    <div class="fenetre-menu-categorie">
                        <h5>Plat</h5>
                        <p id="fenetre-plat-2"></p>
                        <p class="fenetre-allergenes" id="fenetre-allergenes-plat-2"></p>
                    </div>
                    <div class="fenetre-menu-categorie">
                        <h5>Dessert</h5>
                        <p id="fenetre-dessert-2"></p>
                    </div>
                    <button type="button" class="fenetre-choix" id="fenetre-choix-2">Choisir ce menu</button>
                </div>
            </div>
        </div>
    </div>

    <div id="panier">
        <h3>Votre panier</h3>
        <ul id="liste-panier"></ul>
        <p>Total : <span id="total">0</span>€</p>
        <button onclick="validerCommande()">Valider la commande</button>
    </div>

    <footer>
        <div class="contenu-footer">
            <div class="horaires">
                <h3>Horaires</h3>
                <p>Lundi - Vendredi : 10h - 14h et 17h - 21h </p>
                <p>Samedi : 9h30 - 14h30 et 16h30 - 21h30</p>
                <p>Dimanche : Fermé</p>
            </div>
            <div class="liens">
                <a href="MentionsLegales.html">Mentions légales</a>
                <a href="CGV.html">Conditions générales de vente</a>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
