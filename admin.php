<?php
session_start();
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: Connexion.php');
    exit;
}
require 'db.php';
$role = $pdo->query("SELECT r.libelle FROM utilisateur u JOIN role r ON r.role_id = u.role_id WHERE u.utilisateur_id = {$_SESSION['utilisateur_id']}")->fetchColumn();
if ($role !== 'admin') {
    http_response_code(403);
    exit('Accès refusé.');
}
$message = '';
$messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $avisId = (int) ($_POST['avis_id'] ?? 0);
    if (in_array($action, ['valider','refuser','supprimer'], true) && $avisId > 0) {
        if ($action === 'valider') {
            $pdo->prepare('UPDATE avis SET statut = ? WHERE avis_id = ?')->execute(['validé', $avisId]);
            $message = 'Avis validé.';
        } elseif ($action === 'refuser') {
            $pdo->prepare('UPDATE avis SET statut = ? WHERE avis_id = ?')->execute(['refusé', $avisId]);
            $message = 'Avis refusé.';
        } elseif ($action === 'supprimer') {
            $pdo->prepare('DELETE FROM avis WHERE avis_id = ?')->execute([$avisId]);
            $message = 'Avis supprimé.';
        }
        $messageType = 'succes';
    }
}
$avis = $pdo->query("SELECT a.*, u.prenom, u.nom FROM avis a JOIN utilisateur u ON u.utilisateur_id = a.utilisateur_id WHERE a.statut = 'en_attente' ORDER BY a.avis_id DESC")->fetchAll(PDO::FETCH_ASSOC);
$tousLesAvis = $pdo->query("SELECT a.*, u.prenom, u.nom FROM avis a JOIN utilisateur u ON u.utilisateur_id = a.utilisateur_id ORDER BY a.avis_id DESC")->fetchAll(PDO::FETCH_ASSOC);
$stats = $pdo->query('SELECT COUNT(*) AS total_commandes, SUM(prix_menu + prix_livraison) AS chiffre_affaires FROM commande')->fetch(PDO::FETCH_ASSOC);
$avisEnAttente = $pdo->query("SELECT COUNT(*) FROM avis WHERE statut = 'en_attente'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace administrateur</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <body class="admin-espace">
    <nav>
        <ul>
            <li><a href="Accueil.php">Accueil</a></li>
            <li><a href="Menus.php">Menus</a></li>
            <li><a href="espace_utilisateur.php">Mon compte</a></li>
            <li><a href="Contact.php">Contact</a></li>
        </ul>
    </nav>

    <div class="admin-header-bg">
        <header>
            <h1>Espace d'administration</h1>
            <p>Validation des avis et consultation des statistiques.</p>
        </header>
    </div>

    <?php if ($message !== ''): ?>
        <section>
            <p class="note-authentification <?php echo $messageType === 'erreur' ? 'note-erreur' : 'note-succes'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </p>
        </section>
    <?php endif; ?>

    <section>
        <div class="carte">
            <h3>Statistiques</h3>
            <p><strong>Nombre total de commandes :</strong> <?php echo htmlspecialchars($stats['total_commandes'] ?? 0); ?></p>
            <p><strong>Chiffre d'affaires :</strong> <?php echo htmlspecialchars(number_format((float) ($stats['chiffre_affaires'] ?? 0), 2)); ?> €</p>
        </div>
    </section>

    <section>
        <div class="carte carte-large-centree">
            <h3 class="texte-centre">Avis en attente de validation</h3>
            <?php if (empty($avis)): ?>
                <p>Aucun avis en attente.</p>
            <?php else: ?>
                <div class="table-responsive">
                <table class="table-commandes admin-avis-table table-bg">
                    <thead>
                        <tr class="fond-gris-clair">
                            <th class="th-centre">Client</th>
                            <th class="th-centre">Note</th>
                            <th class="th-centre">Commentaire</th>
                            <th class="th-centre">Statut</th>
                            <th class="th-centre">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($avis as $ligne): ?>
                        <tr>
                            <td class="td-centre"> <?php echo htmlspecialchars(trim(($ligne['prenom'] ?? '') . ' ' . ($ligne['nom'] ?? ''))); ?> </td>
                            <td class="td-centre"> <?php echo htmlspecialchars($ligne['note']); ?> </td>
                            <td class="td-centre td-preline"> <?php echo htmlspecialchars($ligne['description']); ?> </td>
                            <td class="td-centre"> <?php echo htmlspecialchars($ligne['statut']); ?> </td>
                            <td class="td-centre">
                                <div class="admin-actions-group">
                                    <form method="post" action="admin.php" class="form-inline form-mr4">
                                        <input type="hidden" name="avis_id" value="<?php echo htmlspecialchars($ligne['avis_id']); ?>">
                                        <button type="submit" name="action" value="valider" class="btn-admin">Valider</button>
                                    </form>
                                    <form method="post" action="admin.php" class="form-inline form-mr4">
                                        <input type="hidden" name="avis_id" value="<?php echo htmlspecialchars($ligne['avis_id']); ?>">
                                        <button type="submit" name="action" value="refuser" class="btn-admin">Refuser</button>
                                    </form>
                                    <form method="post" action="admin.php" class="form-inline">
                                        <input type="hidden" name="avis_id" value="<?php echo htmlspecialchars($ligne['avis_id']); ?>">
                                        <button type="submit" name="action" value="supprimer" class="btn-admin">Supprimer</button>
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
        <div class="carte carte-large-centree">
            <h3 class="texte-centre">Tous les avis clients</h3>
            <?php if (empty($tousLesAvis)): ?>
                <p>Aucun avis client.</p>
            <?php else: ?>
                <div class="table-responsive">
                <table class="table-commandes admin-avis-table table-bg">
                    <thead>
                        <tr>
                            <th class="admin-avis-th th-centre">Client</th>
                            <th class="admin-avis-th th-centre">Commentaire</th>
                            <th class="admin-avis-th th-centre">Note</th>
                            <th class="admin-avis-th th-centre">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($tousLesAvis as $ligne): ?>
                        <tr>
                            <td class="admin-avis-td client td-centre">
                                <?php echo htmlspecialchars(trim(($ligne['prenom'] ?? '') . ' ' . ($ligne['nom'] ?? ''))); ?>
                            </td>
                            <td class="admin-avis-td commentaire td-centre">
                                <?php echo htmlspecialchars($ligne['description']); ?>
                            </td>
                            <td class="admin-avis-td note td-centre">
                                <?php echo htmlspecialchars($ligne['note']); ?>
                            </td>
                            <td class="admin-avis-td action td-centre">
                                <form method="post" action="admin.php" class="admin-avis-form">
                                    <input type="hidden" name="avis_id" value="<?php echo htmlspecialchars($ligne['avis_id']); ?>">
                                    <button type="submit" name="action" value="supprimer" class="btn-admin">Supprimer</button>
                                </form>
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
        <button class="panier-supprimer" type="button" onclick="window.location.href='deconnexion.php'">Déconnexion</button>
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
                <a href="MentionsLegales.html">Mentions légales</a>
                <a href="CGV.html">Conditions générales de vente</a>
            </div>
        </div>
    </footer>
</body>
</html>
