
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: Connexion.php');
    exit;
}
require 'db.php';

$utilisateurId = $_SESSION['utilisateur_id'];

$requeteUser = $pdo->prepare('SELECT nom, prenom, email, telephone, ville, pays, adresse_postale FROM utilisateur WHERE utilisateur_id = :id');
$requeteUser->execute([':id' => $utilisateurId]);
$utilisateur = $requeteUser->fetch(PDO::FETCH_ASSOC);

$role = $pdo->query("SELECT r.libelle FROM utilisateur u JOIN role r ON r.role_id = u.role_id WHERE u.utilisateur_id = $utilisateurId")->fetchColumn();

$requeteCommandes = $pdo->prepare('SELECT numero_commande, date_commande, date_prestation, heure_livraison, prix_menu, prix_livraison, statut FROM commande WHERE utilisateur_id = :id AND (cachee_utilisateur IS NULL OR cachee_utilisateur = 0) ORDER BY date_commande DESC');
$requeteCommandes->execute([':id' => $utilisateurId]);
$commandes = $requeteCommandes->fetchAll(PDO::FETCH_ASSOC);

$avis = $pdo->query("SELECT note, description, statut, numero_commande FROM avis WHERE utilisateur_id = $utilisateurId ORDER BY avis_id DESC")->fetchAll(PDO::FETCH_ASSOC);
$avisParCommande = [];
foreach ($avis as $a) {
    if (!empty($a['numero_commande'])) {
        $avisParCommande[$a['numero_commande']] = $a;
    }
}
$commandesLivreesSansAvis = array_filter($commandes, function($commande) use ($avisParCommande) {
    return stripos($commande['statut'], 'livrée') !== false && empty($avisParCommande[$commande['numero_commande']]);
});
$aCommandeLivree = count($commandesLivreesSansAvis) > 0;

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'modifier_infos') {
        $champs = ['nom','prenom','email','telephone','ville','pays','adresse'];
        $vide = false;
        foreach ($champs as $champ) {
            if (empty(trim($_POST[$champ] ?? ''))) $vide = true;
        }
        if ($vide) {
            $message = 'Merci de remplir tous les champs.';
            $messageType = 'erreur';
        } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $message = 'Adresse e-mail invalide.';
            $messageType = 'erreur';
        } else {
            $maj = $pdo->prepare('UPDATE utilisateur SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone, ville = :ville, pays = :pays, adresse_postale = :adresse WHERE utilisateur_id = :id');
            $maj->execute([
                ':nom' => $_POST['nom'],
                ':prenom' => $_POST['prenom'],
                ':email' => $_POST['email'],
                ':telephone' => $_POST['telephone'],
                ':ville' => $_POST['ville'],
                ':pays' => $_POST['pays'],
                ':adresse' => $_POST['adresse'],
                ':id' => $utilisateurId
            ]);
            $message = 'Informations mises à jour avec succès.';
            $messageType = 'succes';
            $requeteUser->execute([':id' => $utilisateurId]);
            $utilisateur = $requeteUser->fetch(PDO::FETCH_ASSOC);
        }
    }
    if ($action === 'supprimer_commande') {
        $numero = $_POST['numero_commande'] ?? '';
        $statut = $pdo->query("SELECT statut FROM commande WHERE numero_commande = '$numero' AND utilisateur_id = $utilisateurId")->fetchColumn();
        if ($statut && ($statut === 'livrée' || $statut === 'annulée')) {
            $pdo->query("UPDATE commande SET cachee_utilisateur = 1 WHERE numero_commande = '$numero' AND utilisateur_id = $utilisateurId");
            $message = 'Commande masquée.';
            $messageType = 'succes';
        } else {
            $message = 'Suppression impossible : seules les commandes livrées ou annulées peuvent être supprimées.';
            $messageType = 'erreur';
        }
        $requeteCommandes->execute([':id' => $utilisateurId]);
        $commandes = $requeteCommandes->fetchAll(PDO::FETCH_ASSOC);
    }
    if ($action === 'annuler_commande') {
        $numero = $_POST['numero_commande'] ?? '';
        $statut = $pdo->query("SELECT statut FROM commande WHERE numero_commande = '$numero' AND utilisateur_id = $utilisateurId")->fetchColumn();
        if ($statut && $statut === 'nouvelle') {
            $pdo->query("UPDATE commande SET statut = 'annulée' WHERE numero_commande = '$numero'");
            $message = 'Commande annulée.';
            $messageType = 'succes';
        } else {
            $message = 'Annulation impossible : la commande est déjà acceptée.';
            $messageType = 'erreur';
        }
        $requeteCommandes->execute([':id' => $utilisateurId]);
        $commandes = $requeteCommandes->fetchAll(PDO::FETCH_ASSOC);
    }
    if ($action === 'ajouter_avis') {
        $numero_commande = $_POST['numero_commande'] ?? '';
        $note = trim($_POST['note'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $statut = $pdo->query("SELECT statut FROM commande WHERE numero_commande = '$numero_commande' AND utilisateur_id = $utilisateurId")->fetchColumn();
        if (!$statut || stripos($statut, 'livrée') === false) {
            $message = 'Vous pouvez laisser un avis uniquement après une commande livrée.';
            $messageType = 'erreur';
        } elseif ($note === '' || $description === '' || $numero_commande === '') {
            $message = 'Merci de remplir tous les champs.';
            $messageType = 'erreur';
        } elseif (isset($avisParCommande[$numero_commande])) {
            $message = 'Vous avez déjà laissé un avis pour cette commande.';
            $messageType = 'erreur';
        } else {
            $pdo->prepare('INSERT INTO avis (note, description, statut, utilisateur_id, numero_commande) VALUES (:note, :description, :statut, :utilisateur_id, :numero_commande)')
                ->execute([
                    ':note' => $note,
                    ':description' => $description,
                    ':statut' => 'en_attente',
                    ':utilisateur_id' => $utilisateurId,
                    ':numero_commande' => $numero_commande
                ]);
            $message = 'Avis envoyé. Il sera publié après validation.';
            $messageType = 'succes';
        }

        $avis = $pdo->query("SELECT note, description, statut, numero_commande FROM avis WHERE utilisateur_id = $utilisateurId ORDER BY avis_id DESC")->fetchAll(PDO::FETCH_ASSOC);
        $avisParCommande = [];
        foreach ($avis as $a) {
            if (!empty($a['numero_commande'])) {
                $avisParCommande[$a['numero_commande']] = $a;
            }
        }
        $commandesLivreesSansAvis = array_filter($commandes, function($commande) use ($avisParCommande) {
            return stripos($commande['statut'], 'livrée') !== false && empty($avisParCommande[$commande['numero_commande']]);
        });
        $aCommandeLivree = count($commandesLivreesSansAvis) > 0;
    }
}
require 'db.php';
$utilisateur_id = $_SESSION['utilisateur_id'];
$stmt = $pdo->prepare('SELECT prenom, nom, email FROM utilisateur WHERE utilisateur_id = ?');
$stmt->execute([$utilisateur_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Mon compte</title>
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
        <h1>Mon compte</h1>
        <p>Gérez vos commandes et vos informations.</p>
    </header>
    <div class="page-content">

        <?php if ($role === 'employe' || $role === 'admin'): ?>
            <section>
                <p class="note-authentification note-succes">
                    Accès gestion :
                    <?php if ($role === 'employe' || $role === 'admin'): ?>
                        <a href="employe.php">Espace employé</a>
                    <?php endif; ?>
                    <?php if ($role === 'admin'): ?>
                        — <a href="admin.php">Espace administrateur</a>
                    <?php endif; ?>
                </p>
            </section>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
            <section>
                <p class="note-authentification <?php echo $messageType === 'erreur' ? 'note-erreur' : 'note-succes'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            </section>
        <?php endif; ?>

        <div class="compte-commandes carte compte-bloc compte-bloc-large">
            <h3>Mes commandes</h3>
            <?php if (empty($commandes)): ?>
                <p>Aucune commande.</p>
            <?php else: ?>
                <div class="table-commandes">
                    <div class="table-ligne table-entete">
                        <span>Numéro</span>
                        <span>Date</span>
                        <span>Prestation</span>
                        <span>Statut</span>
                        <span>Total</span>
                        <span>Action</span>
                        <span>Avis</span>
                    </div>
                    <?php foreach ($commandes as $commande): ?>
                        <div class="table-ligne">
                            <span><?php echo htmlspecialchars($commande['numero_commande']); ?></span>
                            <span><?php echo htmlspecialchars($commande['date_commande']); ?></span>
                            <span><?php echo htmlspecialchars($commande['date_prestation']); ?></span>
                            <span><?php echo htmlspecialchars($commande['statut']); ?></span>
                            <span><?php echo htmlspecialchars($commande['prix_menu'] + $commande['prix_livraison']); ?>€</span>
                            <span>
                                <?php if ($commande['statut'] === 'nouvelle'): ?>
                                    <form method="post" action="espace_utilisateur.php">
                                        <input type="hidden" name="action" value="annuler_commande">
                                        <input type="hidden" name="numero_commande" value="<?php echo htmlspecialchars($commande['numero_commande']); ?>">
                                        <button class="panier-supprimer" type="submit">Annuler</button>
                                    </form>
                                <?php elseif ($commande['statut'] === 'livrée' || $commande['statut'] === 'annulée'): ?>
                                    <div class="commande-actions-centre">
                                        <form method="post" action="espace_utilisateur.php">
                                            <input type="hidden" name="action" value="supprimer_commande">
                                            <input type="hidden" name="numero_commande" value="<?php echo htmlspecialchars($commande['numero_commande']); ?>">
                                            <button class="panier-supprimer bouton-supprimer-commande" type="submit">Supprimer</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="compte-grille compte-grille-infos-avis">
            <div class="compte-colonne compte-colonne-gauche">
                <div class="carte-authentification compte-bloc compte-infos">
                    <h3>Mes informations</h3>
                    <form method="post" action="espace_utilisateur.php">
                        <input type="hidden" name="action" value="modifier_infos">
                        <label for="nom">Nom</label>
                        <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($utilisateur['nom'] ?? ''); ?>" required>
                        <label for="prenom">Prénom</label>
                        <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($utilisateur['prenom'] ?? ''); ?>" required>
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($utilisateur['email'] ?? ''); ?>" required>
                        <label for="telephone">Téléphone</label>
                        <input type="text" id="telephone" name="telephone" value="<?php echo htmlspecialchars($utilisateur['telephone'] ?? ''); ?>" required>
                        <label for="ville">Ville</label>
                        <input type="text" id="ville" name="ville" value="<?php echo htmlspecialchars($utilisateur['ville'] ?? ''); ?>" required>
                        <label for="pays">Pays</label>
                        <input type="text" id="pays" name="pays" value="<?php echo htmlspecialchars($utilisateur['pays'] ?? ''); ?>" required>
                        <label for="adresse">Adresse</label>
                        <input type="text" id="adresse" name="adresse" value="<?php echo htmlspecialchars($utilisateur['adresse_postale'] ?? ''); ?>" required>
                        <button type="submit">Mettre à jour</button>
                    </form>
                </div>
            </div>
            <div class="compte-colonne compte-colonne-droite">
                <div class="carte compte-bloc compte-bloc-large compte-avis carte-large-centree">
                    <h3>Mes avis</h3>
                    <?php if ($role === 'utilisateur' && $aCommandeLivree): ?>
                        <form method="post" action="espace_utilisateur.php">
                            <input type="hidden" name="action" value="ajouter_avis">
                            <label for="numero_commande">Commande livrée à évaluer</label>
                            <select id="numero_commande" name="numero_commande" required>
                                <option value="">Choisir une commande</option>
                                <?php foreach ($commandesLivreesSansAvis as $commande): ?>
                                    <option value="<?php echo htmlspecialchars($commande['numero_commande']); ?>">
                                        <?php echo htmlspecialchars($commande['numero_commande'] . ' - ' . $commande['date_prestation']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="note-avis">Note (1 à 5)</label>
                            <select id="note-avis" name="note" required>
                                <option value="">Choisir</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                            </select>
                            <div class="mt-12"></div>
                            <label for="description-avis">Commentaire</label><br>
                            <textarea id="description-avis" name="description" placeholder="Votre avis" required class="textarea-avis"></textarea>
                            <div class="mt-12"></div>
                            <button type="submit">Envoyer mon avis</button>
                        </form>
                    <?php elseif ($role === 'utilisateur'): ?>
                        <p>Vous pourrez laisser un avis après une commande livrée.</p>
                    <?php endif; ?>

                    <?php if (!empty($avis)): ?>
                        <div class="avis-table-wrapper">
                            <div class="flex-centre">
                                <table class="table-bg table-avis-centree">
                                    <thead>
                                        <tr class="fond-gris-clair">
                                            <th class="th-gauche">Note</th>
                                            <th class="th-gauche">Commentaire</th>
                                            <th class="th-gauche">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($avis as $ligne): ?>
                                            <tr>
                                                <td class="td-gauche"><?php echo htmlspecialchars($ligne['note']); ?></td>
                                                <td class="td-gauche td-preline"><?php echo htmlspecialchars($ligne['description']); ?></td>
                                                <td class="td-gauche"><?php echo htmlspecialchars($ligne['statut']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <p>Aucun avis pour le moment.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <div class="zone-deconnexion">
        <button class="panier-supprimer" type="button" onclick="window.location.href='deconnexion.php'">Se déconnecter</button>
    </div>
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
                <a href="MentionsLegales.html">Mentions Légales</a>
                <a href="CGV.html">Conditions Générales de Vente</a>
            </div>
        </div>
    </footer>
</body>
</html>
