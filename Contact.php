<?php
require 'mailer.php';
$message = '';
$messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contenu = trim($_POST['message'] ?? '');
    if ($nom === '' || $email === '' || $contenu === '') {
        $message = 'Merci de remplir tous les champs.';
        $messageType = 'erreur';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Adresse e-mail invalide.';
        $messageType = 'erreur';
    } else {
        global $mailConfig;
        $destinataire = $mailConfig['reply_to'] ?? $mailConfig['from_email'] ?? '';
        if ($destinataire === '') {
            $message = 'Adresse de réception non configurée.';
            $messageType = 'erreur';
        } else {
            $sujet = 'Message de contact - ' . $nom;
            $corps = "Nom : {$nom}\nEmail : {$email}\n\nMessage :\n{$contenu}";
            $envoye = envoyerMailSmtp($destinataire, $sujet, $corps);
            if ($envoye) {
                $message = 'Merci, votre message a bien été envoyé.';
                $messageType = 'succes';
            } else {
                $message = 'Envoi impossible pour le moment.';
                $messageType = 'erreur';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vite et Gourmand - Contactez-nous</title>
    <link rel="stylesheet" href="styles.css?v=1">
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

    <h2>Nous contacter</h2>

    <?php if ($message !== ''): ?>
        <section>
            <p class="note-authentification <?php echo $messageType === 'erreur' ? 'note-erreur' : 'note-succes'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </p>
        </section>
    <?php endif; ?>

    <form class="formulaire-contact" action="Contact.php" method="post">
        <label for="nom">Nom&nbsp;:</label>
        <input type="text" id="nom" name="nom" placeholder="Votre nom" value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>" required><br><br>

        <label for="email">Adresse e-mail&nbsp;:</label>
        <input type="email" id="email" name="email" placeholder="johnsnow@exemple.fr" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required><br><br>

        <label for="message">Votre message&nbsp;:</label>
        <textarea id="message" name="message" placeholder="Saisissez votre message ici..." required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea><br><br>

        <button type="submit">Envoyer le message</button>
    </form>

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
