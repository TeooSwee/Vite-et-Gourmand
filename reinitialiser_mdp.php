session_start();
<?php
include 'db.php';
session_start();
$message = '';
function genererToken($length = 32) {
    return bin2hex(random_bytes($length));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Adresse email invalide.";
    } else {
        $stmt = $pdo->prepare('SELECT utilisateur_id FROM utilisateur WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user) {
            $token = genererToken();
            $expire = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $stmt = $pdo->prepare('UPDATE utilisateur SET reset_token = ?, reset_token_expire = ? WHERE utilisateur_id = ?');
            $stmt->execute([$token, $expire, $user['utilisateur_id']]);
            require 'mailer.php';
            $lien = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reinitialiser_mdp.php?token=$token";
            envoyerMailSmtp($email, 'Réinitialisation de mot de passe', "Cliquez sur ce lien pour réinitialiser votre mot de passe : $lien");
            $message = "Un email de réinitialisation a été envoyé.";
        } else {
            $erreur = "Aucun compte trouvé pour cet email.";
        }
    }
}
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $pdo->prepare('SELECT utilisateur_id, reset_token_expire FROM utilisateur WHERE reset_token = ?');
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if ($user && strtotime($user['reset_token_expire']) > time()) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nouveau_mdp'])) {
            $nouveauMdp = $_POST['nouveau_mdp'];
            $hash = password_hash($nouveauMdp, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE utilisateur SET mot_de_passe = ?, reset_token = NULL, reset_token_expire = NULL WHERE utilisateur_id = ?');
            $stmt->execute([$hash, $user['utilisateur_id']]);
            $message = "Mot de passe réinitialisé.";
        }
    } else {
        $erreur = "Lien de réinitialisation invalide ou expiré.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le mot de passe</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="site-container">
        <nav>
            <ul>
                <li><a href="Accueil.php">Accueil</a></li>
                <li><a href="Menus.php">Menus</a></li>
                <li><a href="Connexion.php">Connexion</a></li>
                <li><a href="Contact.php">Contact</a></li>
            </ul>
        </nav>
        <header>
            <h1>Réinitialiser le mot de passe</h1>
            <p>Choisissez un nouveau mot de passe sécurisé.</p>
        </header>
    </div>

    <?php if ($message !== ''): ?>
        <section>
            <p class="note-authentification <?php echo $messageType === 'erreur' ? 'note-erreur' : 'note-succes'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </p>
        </section>
    <?php endif; ?>

    <?php if ($messageType !== 'succes'): ?>
        <section class="zone-authentification">
            <div class="carte-authentification">
                <h3>Nouveau mot de passe</h3>
                <form method="post" action="reinitialiser_mdp.php">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <label for="motdepasse-nouveau">Mot de passe</label>
                    <input type="password" id="motdepasse-nouveau" name="motdepasse" placeholder="10 caractères minimum, avec Aa1!" required>

                    <label for="motdepasse-confirmation">Confirmer le mot de passe</label>
                    <input type="password" id="motdepasse-confirmation" name="confirmation" placeholder="Répétez le mot de passe" required>

                    <button type="submit">Mettre à jour</button>
                </form>
            </div>
        </section>
    <?php endif; ?>

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
