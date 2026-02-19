<?php
include 'db.php';
include 'mailer.php';

session_start();

function creerTokenReinitialisation($pdo, $email)
{
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);

    $suppression = $pdo->prepare('DELETE FROM reinitialisations_mdp WHERE email = :email');
    $suppression->execute([':email' => $email]);

    $insertion = $pdo->prepare('INSERT INTO reinitialisations_mdp (email, token_hash) VALUES (:email, :token_hash)');
    $insertion->execute([
        ':email' => $email,
        ':token_hash' => $hash
    ]);

    return $token;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'inscription') {
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $email = $_POST['email'];
        $telephone = $_POST['telephone'];
        $ville = $_POST['ville'];
        $pays = $_POST['pays'];
        $adresse = $_POST['adresse'];
        $motdepasse = $_POST['motdepasse'];

        if ($nom === '' || $prenom === '' || $telephone === '' || $ville === '' || $pays === '' || $adresse === '' || $email === '' || $motdepasse === '') {
            $message = 'Merci de remplir tous les champs.';
            $messageType = 'erreur';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Adresse e-mail invalide.';
            $messageType = 'erreur';  
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{10,}$/', $motdepasse)) {
            $message = 'Le mot de passe doit contenir au moins 10 caractères, dont 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial.';
            $messageType = 'erreur';
        } else {
            $hash = password_hash($motdepasse, PASSWORD_DEFAULT);
            $requete = $pdo->prepare('INSERT INTO utilisateur (nom, prenom, email, telephone, ville, pays, adresse_postale, password, role_id) VALUES (:nom, :prenom, :email, :telephone, :ville, :pays, :adresse, :password, 1)');
            try {
                $requete->execute([
                    ':nom' => $nom,
                    ':prenom' => $prenom,
                    ':email' => $email,
                    ':telephone' => $telephone,
                    ':ville' => $ville,
                    ':pays' => $pays,
                    ':adresse' => $adresse,
                    ':password' => $hash
                ]);
                $message = 'Inscription réussie. Vous pouvez vous connecter.';
                $messageType = 'succes';
            } catch (PDOException $e) {
                $message = 'Une erreur est survenue.';
                $messageType = 'erreur';
            }
        }
    }

    if ($action === 'connexion') {
        $email = $_POST['email'];
        $motdepasse = $_POST['motdepasse'];

        if ($email === '' || $motdepasse === '') {
            $message = 'Merci de renseigner votre e-mail et votre mot de passe.';
            $messageType = 'erreur';
        } else {
            $requete = $pdo->prepare('SELECT utilisateur_id, prenom, email, password FROM utilisateur WHERE email = :email');
            $requete->execute([':email' => $email]);
            $utilisateur = $requete->fetch(PDO::FETCH_ASSOC);

            if ($utilisateur && password_verify($motdepasse, $utilisateur['password'])) {
                $_SESSION['utilisateur_id'] = $utilisateur['utilisateur_id'];
                $_SESSION['utilisateur_prenom'] = $utilisateur['prenom'];
                $_SESSION['utilisateur_email'] = $utilisateur['email'];
                $message = 'Connexion réussie. Vous pouvez commander.';
                $messageType = 'succes';
            } else {
                $message = 'Identifiants incorrects.';
                $messageType = 'erreur';
            }
        }
    }

    if ($action === 'motdepasse_oublie') {
        $emailRecup = trim($_POST['email_recup'] ?? '');

        if ($emailRecup === '' || !filter_var($emailRecup, FILTER_VALIDATE_EMAIL)) {
            $message = 'Merci de renseigner une adresse e-mail valide.';
            $messageType = 'erreur';
        } else {
            $requete = $pdo->prepare('SELECT utilisateur_id, prenom FROM utilisateur WHERE email = :email');
            $requete->execute([':email' => $emailRecup]);
            $utilisateur = $requete->fetch(PDO::FETCH_ASSOC);

            if ($utilisateur) {
                $token = creerTokenReinitialisation($pdo, $emailRecup);
                $lien = 'http://localhost:8888/Evaluations/reinitialiser_mdp.php?email=' . urlencode($emailRecup) . '&token=' . urlencode($token);
                $sujet = 'Réinitialisation de votre mot de passe';
                $messageMail = "Bonjour {$utilisateur['prenom']},\n\nPour réinitialiser votre mot de passe, cliquez sur ce lien :\n{$lien}";
                envoyerMailSmtp($emailRecup, $sujet, $messageMail);
            }

            $message = 'Si un compte existe, un e-mail de réinitialisation a été envoyé.';
            $messageType = 'succes';
        }
    }
}

$estConnecte = isset($_SESSION['utilisateur_id']);
$nomUtilisateur = $_SESSION['utilisateur_prenom'] ?? '';
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vite et Gourmand - Connexion</title>
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
        <h1>Se connecter ou créer un compte</h1>
        <p>Accédez à votre compte ou créez-en un pour commander rapidement.</p>
    </header>

    <?php if ($message !== ''): ?>
        <section>
            <p class="note-authentification<?php echo $messageType === 'erreur' ? ' note-erreur' : ' note-succes'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </p>
        </section>
    <?php endif; ?>

    <?php if ($estConnecte): ?>
        <section>
            <p class="note-authentification note-succes">Bienvenue <?php echo htmlspecialchars($nomUtilisateur); ?>, vous êtes connecté.</p>
            <button class="panier-supprimer" type="button" onclick="window.location.href='deconnexion.php'">Se déconnecter</button>
        </section>
    <?php else: ?>
        <section class="zone-authentification grille-authentification">
            <div class="carte-authentification">
                <h3>Connexion</h3>
                <form method="post" action="Connexion.php">
                    <input type="hidden" name="action" value="connexion">

                    <label for="email-connexion">E-mail</label>
                    <input type="email" id="email-connexion" name="email" placeholder="johnsnow@exemple.fr" required>

                    <label for="motdepasse-connexion">Mot de passe</label>
                    <input type="password" id="motdepasse-connexion" name="motdepasse" placeholder="••••••••" required>

                    <button type="submit">Se connecter</button>
                </form>

                <div class="bloc-reinitialisation">
                    <p class="texte-secondaire">Mot de passe oublié ?</p>
                    <form method="post" action="Connexion.php">
                        <input type="hidden" name="action" value="motdepasse_oublie">

                        <label for="email-recuperation">E-mail</label>
                        <input type="email" id="email-recuperation" name="email_recup" placeholder="johnsnow@exemple.fr" required>

                        <button type="submit">Réinitialiser</button>
                    </form>
                </div>
            </div>

            <div class="carte-authentification">
                <h3>Créer un compte</h3>
                <form method="post" action="Connexion.php">
                    <input type="hidden" name="action" value="inscription">

                    <label for="nom-inscription">Nom</label>
                    <input type="text" id="nom-inscription" name="nom" placeholder="Votre nom" required>

                    <label for="prenom-inscription">Prénom</label>
                    <input type="text" id="prenom-inscription" name="prenom" placeholder="Votre prénom" required>

                    <label for="email-inscription">E-mail</label>
                    <input type="email" id="email-inscription" name="email" placeholder="johnsnow@exemple.fr" required>

                    <label for="telephone-inscription">Téléphone portable</label>
                    <input type="tel" id="telephone-inscription" name="telephone" placeholder="06 01 02 03 04" required>

                    <label for="ville-inscription">Ville</label>
                    <input type="text" id="ville-inscription" name="ville" placeholder="Bordeaux" required>

                    <label for="pays-inscription">Pays</label>
                    <input type="text" id="pays-inscription" name="pays" placeholder="France" required>

                    <label for="adresse-inscription">Adresse postale</label>
                    <input type="text" id="adresse-inscription" name="adresse" placeholder="1 rue Victor Hugo" required>

                    <label for="motdepasse-inscription">Mot de passe</label>
                    <input type="password" id="motdepasse-inscription" name="motdepasse" placeholder="10 caractères minimum, avec Aa1!" required>

                    <button type="submit">Créer un compte</button>
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
