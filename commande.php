<?php
require 'db.php';
session_start();
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: Connexion.php');
    exit;
}
$utilisateur_id = $_SESSION['utilisateur_id'];
$stmt = $pdo->prepare('SELECT * FROM commande WHERE utilisateur_id = ? ORDER BY date_commande DESC');
$stmt->execute([$utilisateur_id]);
 $commandes = $stmt->fetchAll();
?>
<body class="page-commande">
    <div class="site-container">
        <nav>
            <ul>
                <li><a href="Accueil.php">Accueil</a></li>
                <li><a href="Menus.php">Menus</a></li>
                <li><a href="espace_utilisateur.php">Mon compte</a></li>
                <li><a href="Contact.php">Contact</a></li>
            </ul>
        </nav>
        <header>
            <h1>Votre commande</h1>
            <p>Vérifiez votre panier et validez votre commande.</p>
        </header>
    </div>

    <section>
        <div class="carte">
            <h3>Informations du client</h3>
            <p><strong>Prénom :</strong> <?php echo htmlspecialchars($client['prenom'] ?? ''); ?></p>
            <p><strong>E-mail :</strong> <?php echo htmlspecialchars($client['email'] ?? ''); ?></p>
            <p><strong>Téléphone :</strong> <?php echo htmlspecialchars($client['telephone'] ?? ''); ?></p>
        </div>
    </section>

    <section>
        <div class="carte">
            <h3>Adresse de livraison</h3>
            <div class="adresse-grille">
                <div class="plein">
                    <label for="adresse-commande">Adresse postale</label>
                    <input type="text" id="adresse-commande" placeholder="1 rue Victor Hugo" value="<?php echo htmlspecialchars($client['adresse_postale'] ?? ''); ?>">
                </div>

                <div>
                    <label for="ville-commande">Ville</label>
                    <input type="text" id="ville-commande" placeholder="Bordeaux" value="<?php echo htmlspecialchars($client['ville'] ?? ''); ?>">
                </div>

                <div>
                    <label for="pays-commande">Pays</label>
                    <input type="text" id="pays-commande" placeholder="France" value="<?php echo htmlspecialchars($client['pays'] ?? ''); ?>">
                </div>

                <div>
                    <label for="date-commande">Date de prestation</label>
                    <input type="date" id="date-commande">
                </div>

                <div>
                    <label for="heure-commande">Heure de livraison</label>
                    <input type="time" id="heure-commande">
                </div>

                <div>
                    <label for="distance-commande">Distance (km)</label>
                    <input type="number" id="distance-commande" min="0" step="0.1" value="0">
                    <small>Si vous êtes à Bordeaux, laissez 0 km.</small>
                </div>

                <div>
                    <label for="pret-materiel">Prêt de matériel</label>
                    <select id="pret-materiel">
                        <option value="0">Non</option>
                        <option value="1">Oui</option>
                    </select>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="carte">
            <h3>Récapitulatif</h3>
            <ul id="commande-liste"></ul>
            <p>Personnes : <span id="commande-personnes">0</span></p>
            <p>Prix menu : <span id="commande-prix-menu">0</span>€</p>
            <p id="commande-reduction" class="reduction-cachee">Réduction : -<span id="commande-montant-reduction">0</span>€</p>
            <p>Prix livraison : <span id="commande-prix-livraison">0</span>€</p>
            <p>Total : <span id="commande-total">0</span>€</p>
        </div>
    </section>

    <section>
        <div class="carte">
            <h3>Confirmation</h3>
            <p>En confirmant, votre commande sera enregistrée.</p>
            <button id="commande-confirmer" type="button">Confirmer la commande</button>
            <p id="commande-message" class="note-authentification message-cache"></p>
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

    <script>
        const liste = document.getElementById('commande-liste');
        const personnesEl = document.getElementById('commande-personnes');
        const prixMenuEl = document.getElementById('commande-prix-menu');
        const prixLivraisonEl = document.getElementById('commande-prix-livraison');
            const reductionEl = document.getElementById('commande-reduction');
            const montantReductionEl = document.getElementById('commande-montant-reduction');
        const totalEl = document.getElementById('commande-total');
        const villeInput = document.getElementById('ville-commande');
        const distanceInput = document.getElementById('distance-commande');
        const dateInput = document.getElementById('date-commande');
        const heureInput = document.getElementById('heure-commande');
        const adresseInput = document.getElementById('adresse-commande');
        const paysInput = document.getElementById('pays-commande');
        const pretMaterielInput = document.getElementById('pret-materiel');
        const messageEl = document.getElementById('commande-message');
        const boutonConfirmer = document.getElementById('commande-confirmer');

        const panier = JSON.parse(localStorage.getItem('panierCommande') || '[]');
        const totalInitial = Number(localStorage.getItem('totalCommande') || 0);
        let prixMenu = totalInitial;
        let prixLivraison = 0;

        if (panier.length === 0) {
            const li = document.createElement('li');
            li.textContent = 'Votre panier est vide.';
            liste.appendChild(li);
        } else {
            panier.forEach(item => {
                const li = document.createElement('li');
                const nomAvecChoix = item.choix ? `${item.nom} (${item.choix})` : item.nom;
                li.textContent = `${nomAvecChoix} x${item.quantite} - ${item.sousTotal}€`;
                liste.appendChild(li);
            });
        }

        const calculerTotal = () => {
            const ville = (villeInput.value || '').trim().toLowerCase();
            const distance = Number(distanceInput.value || 0);
            prixLivraison = 0;
            if (ville && ville !== 'bordeaux') {
                prixLivraison = 5 + (0.59 * distance);
            }

            const nombrePersonnes = panier.reduce((acc, item) => acc + Number(item.quantite || 0), 0);
            personnesEl.textContent = nombrePersonnes;

            
            let totalMenu = 0;
            let totalReduction = 0;
            const menusRegroupes = {};
            panier.forEach(item => {
                const nom = item.nom;
                if (!menusRegroupes[nom]) {
                    menusRegroupes[nom] = {
                        prix: Number(item.prix || 0),
                        minimum: Number(item.minimum || 0),
                        quantite: 0
                    };
                }
                menusRegroupes[nom].quantite += Number(item.quantite || 0);
            });

            
            Object.values(menusRegroupes).forEach(menu => {
                let sousTotal = menu.prix * menu.quantite;
                let reduction = 0;
                if (menu.minimum && menu.quantite >= menu.minimum + 5) {
                    reduction = sousTotal * 0.1;
                    sousTotal -= reduction;
                }
                totalReduction += reduction;
                totalMenu += sousTotal;
            });

            if (totalReduction > 0) {
                reductionEl.style.display = '';
                montantReductionEl.textContent = totalReduction.toFixed(2);
            } else {
                reductionEl.style.display = 'none';
                montantReductionEl.textContent = '0';
            }

            prixMenu = totalMenu;
            prixMenuEl.textContent = prixMenu.toFixed(2);
            prixLivraisonEl.textContent = prixLivraison.toFixed(2);
            totalEl.textContent = (prixMenu + prixLivraison).toFixed(2);
        };

        calculerTotal();

        [villeInput, distanceInput].forEach(input => {
            input.addEventListener('input', calculerTotal);
        });

        boutonConfirmer.addEventListener('click', async () => {
            if (panier.length === 0) {
                messageEl.textContent = 'Impossible de confirmer : panier vide.';
                messageEl.className = 'note-authentification note-erreur';
                messageEl.style.display = 'block';
                return;
            }

            if (!dateInput.value || !heureInput.value) {
                messageEl.textContent = 'Merci de renseigner la date et l\'heure de livraison.';
                messageEl.className = 'note-authentification note-erreur';
                messageEl.style.display = 'block';
                return;
            }

            try {
                const reponse = await fetch('enregistrer_commande.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        items: panier,
                        total: prixMenu,
                        prix_livraison: prixLivraison,
                        date_prestation: dateInput.value,
                        heure_livraison: heureInput.value,
                        ville: villeInput.value,
                        pays: paysInput.value,
                        adresse: adresseInput.value,
                        pret_materiel: pretMaterielInput.value
                    })
                });
                const data = await reponse.json();

                if (!reponse.ok || !data.success) {
                    throw new Error(data.message || 'Erreur');
                }

                localStorage.removeItem('panierCommande');
                localStorage.removeItem('totalCommande');
                messageEl.textContent = `Commande confirmée. Merci ! Numéro : ${data.numero_commande}`;
                messageEl.className = 'note-authentification note-succes';
                messageEl.style.display = 'block';
            } catch (e) {
                messageEl.textContent = e.message || 'Erreur lors de la confirmation. Réessaie.';
                messageEl.className = 'note-authentification note-erreur';
                messageEl.style.display = 'block';
            }
        });
    </script>
</body>
</html>
