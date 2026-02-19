<?php
session_start();
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: Connexion.php');
    exit;
}
require 'db.php';
$avisMessage = '';
$avisMessageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['avis_action'])) {
    $avisId = (int) ($_POST['avis_id'] ?? 0);
    if ($avisId > 0) {
        if ($_POST['avis_action'] === 'accepter') {
            $pdo->prepare('UPDATE avis SET statut = ? WHERE avis_id = ?')->execute(['validé', $avisId]);
            $avisMessage = 'Avis validé.';
            $avisMessageType = 'succes';
        } elseif ($_POST['avis_action'] === 'refuser') {
            $pdo->prepare('UPDATE avis SET statut = ? WHERE avis_id = ?')->execute(['refusé', $avisId]);
            $avisMessage = 'Avis refusé.';
            $avisMessageType = 'succes';
        }
    }
}
$avisEnAttente = $pdo->query("SELECT a.*, u.prenom, u.nom FROM avis a JOIN utilisateur u ON u.utilisateur_id = a.utilisateur_id WHERE a.statut = 'en_attente' ORDER BY a.avis_id DESC")->fetchAll(PDO::FETCH_ASSOC);
$roleCheck = $pdo->prepare('SELECT r.libelle FROM utilisateur u JOIN role r ON r.role_id = u.role_id WHERE u.utilisateur_id = :id');
$roleCheck->execute([':id' => $_SESSION['utilisateur_id']]);
$role = $roleCheck->fetchColumn();
if (!in_array($role, ['employe', 'admin'], true)) {
    http_response_code(403);
    exit('Accès refusé.');
}
$message = '';
$messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $numero = $_POST['numero_commande'] ?? '';
    if ($action === 'modifier_statut') {
        $statut = $_POST['statut'] ?? '';
        $autorisés = ['nouvelle', 'accepté', 'en préparation', 'en livraison', 'livrée', 'annulée', 'refusée'];
        if ($numero !== '' && in_array($statut, $autorisés, true)) {
            $maj = $pdo->prepare('UPDATE commande SET statut = :statut WHERE numero_commande = :numero');
            $maj->execute([':statut' => $statut, ':numero' => $numero]);
            $message = 'Statut mis à jour.';
            $messageType = 'succes';
            if ($statut === 'en livraison') {
                require_once 'mailer.php';
                $commande = $pdo->prepare('SELECT c.*, u.prenom, u.nom, u.email, u.telephone, u.ville, u.pays, u.adresse_postale FROM commande c JOIN utilisateur u ON u.utilisateur_id = c.utilisateur_id WHERE c.numero_commande = :numero');
                $commande->execute([':numero' => $numero]);
                $infos = $commande->fetch(PDO::FETCH_ASSOC);
                if ($infos && !empty($infos['email'])) {
                    $sujet = 'Votre commande est en cours de livraison';
                    $messageMail = "Bonjour " . htmlspecialchars($infos['prenom']) . ",\n\n";
                    $messageMail .= "Bonne nouvelle, votre commande n°" . htmlspecialchars($infos['numero_commande']) . " est maintenant en cours de livraison !\n";
                    $messageMail .= "\nAdresse de livraison :\n";
                    $messageMail .= htmlspecialchars($infos['adresse_postale']) . ', ' . htmlspecialchars($infos['ville']) . ', ' . htmlspecialchars($infos['pays']) . "\n";
                    $messageMail .= "Heure de livraison prévue : " . htmlspecialchars($infos['heure_livraison']) . "\n";
                    $messageMail .= "Montant total : " . (htmlspecialchars($infos['prix_menu'] + $infos['prix_livraison'])) . " €\n";
                    $messageMail .= "\nMerci pour votre confiance !\nL'équipe Vite et Gourmand";
                    envoyerMailSmtp($infos['email'], $sujet, $messageMail);
                }
            }
        }
    }
    if ($action === 'restitution') {
        $restitution = isset($_POST['restitution_materiel']) ? 1 : 0;
        if ($numero !== '') {
            $maj = $pdo->prepare('UPDATE commande SET restitution_materiel = :restitution WHERE numero_commande = :numero');
            $maj->execute([':restitution' => $restitution, ':numero' => $numero]);
            $message = 'Restitution mise à jour.';
            $messageType = 'succes';
        }
    }
}
$commandes = $pdo->query(
    "SELECT c.*, u.prenom, u.nom, u.email FROM commande c JOIN utilisateur u ON u.utilisateur_id = c.utilisateur_id WHERE c.statut NOT IN ('livrée', 'annulée', 'refusée') ORDER BY c.date_commande DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace employé</title>
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
        <h1>Espace employé</h1>
        <p>Gestion des commandes.</p>
    </header>

    <?php if ($message !== ''): ?>
        <section>
            <p class="note-authentification <?php echo $messageType === 'erreur' ? 'note-erreur' : 'note-succes'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </p>
        </section>
    <?php endif; ?>

    <section>
        <div class="carte carte-commandes">
            <h3>Commandes en cours</h3>
            <?php if (empty($commandes)): ?>
                <p>Aucune commande n'est en cours.</p>
            <?php else: ?>
                <div class="table-commandes">
                    <div class="table-ligne table-entete">
                        <span>Numéro</span>
                        <span>Client</span>
                        <span>Date</span>
                        <span>Statut</span>
                        <span>Total</span>
                        <span>Actions</span>
                    </div>
                    <?php foreach ($commandes as $commande): ?>
                        <div class="table-ligne">
                            <span><?php echo htmlspecialchars($commande['numero_commande']); ?></span>
                            <span><?php echo htmlspecialchars(trim(($commande['prenom'] ?? '') . ' ' . ($commande['nom'] ?? ''))); ?></span>
                            <span><?php echo htmlspecialchars($commande['date_commande']); ?></span>
                            <span><?php echo htmlspecialchars($commande['statut']); ?></span>
                            <span><?php echo htmlspecialchars($commande['prix_menu'] + $commande['prix_livraison']); ?>€</span>
                            <span>
                                <form method="post" action="employe.php" class="form-statut-commande">
                                    <input type="hidden" name="action" value="modifier_statut">
                                    <input type="hidden" name="numero_commande" value="<?php echo htmlspecialchars($commande['numero_commande']); ?>">
                                    <select name="statut">
                                        <?php
                                        $statuts = ['nouvelle', 'accepté', 'en préparation', 'en livraison','livrée', 'annulée'];
                                        foreach ($statuts as $statut): ?>
                                            <option value="<?php echo htmlspecialchars($statut); ?>" <?php echo $commande['statut'] === $statut ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($statut); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit">Mettre à jour</button>
                                </form>
                                <form method="post" action="employe.php">
                                    <input type="hidden" name="action" value="restitution">
                                    <input type="hidden" name="numero_commande" value="<?php echo htmlspecialchars($commande['numero_commande']); ?>">
                                    <label>
                                        <input type="checkbox" name="restitution_materiel" value="1" <?php echo (int) $commande['restitution_materiel'] === 1 ? 'checked' : ''; ?>>
                                        Restitution matériel
                                    </label>
                                    <button type="submit">Valider</button>
                                </form>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="section-centree">
        <div class="carte compte-bloc carte-large-centree">
            <h3>Gestion des avis en attente</h3>
            <?php if ($avisMessage): ?>
                <p class="note-authentification <?php echo $avisMessageType === 'erreur' ? 'note-erreur' : 'note-succes'; ?>">
                    <?php echo htmlspecialchars($avisMessage); ?>
                </p>
            <?php endif; ?>
            <?php if (empty($avisEnAttente)): ?>
                <p>Aucun avis en attente.</p>
            <?php else: ?>
                    <div class="table-responsive">
                    <table class="table-commandes">
                        <thead>
                            <tr class="fond-gris-clair">
                                <th class="th-centre">Client</th>
                                <th class="th-centre">Note</th>
                                <th class="th-gauche">Commentaire</th>
                                <th class="th-centre">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($avisEnAttente as $ligne): ?>
                            <tr>
                                <td class="td-centre"> <?php echo htmlspecialchars(trim(($ligne['prenom'] ?? '') . ' ' . ($ligne['nom'] ?? ''))); ?> </td>
                                <td class="td-centre"> <?php echo htmlspecialchars($ligne['note']); ?> </td>
                                <td class="td-gauche"> <?php echo htmlspecialchars($ligne['description']); ?> </td>
                                <td class="td-centre">
                                    <div class="actions-col">
                                        <form method="post" action="employe.php" class="form-inline">
                                            <input type="hidden" name="avis_id" value="<?php echo htmlspecialchars($ligne['avis_id']); ?>">
                                            <button type="submit" name="avis_action" value="accepter">Accepter</button>
                                        </form>
                                        <form method="post" action="employe.php" class="form-inline">
                                            <input type="hidden" name="avis_id" value="<?php echo htmlspecialchars($ligne['avis_id']); ?>">
                                            <button type="submit" name="avis_action" value="refuser">Refuser</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
            <?php endif; ?>
        </div>
    </section>

    <section>
        <button class="panier-supprimer" type="button" onclick="window.location.href='deconnexion.php'">Se déconnecter</button>
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
</body>
</html>
