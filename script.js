let panier = [];
let total = 0;
function ajouterAuPanier(nom, prix, minimumPersonnes, choix) {
    const libelleChoix = choix ? `Choix ${choix}` : null;
    const itemExistant = panier.find(item => item.nom === nom && item.prix === prix && item.choix === libelleChoix);
    if (itemExistant) {
        itemExistant.quantite += 1;
        itemExistant.sousTotal += prix;
    } else {
        panier.push({ nom, prix, quantite: 1, sousTotal: prix, minimum: minimumPersonnes, choix: libelleChoix });
    }
    total += prix;
    mettreAJourPanier();
}
function retirerDuPanier(index) {
    const item = panier[index];
    if (!item) return;
    item.quantite -= 1;
    item.sousTotal -= item.prix;
    total -= item.prix;
    if (item.quantite <= 0) panier.splice(index, 1);
    if (total < 0) total = 0;
    mettreAJourPanier();
}
function mettreAJourPanier() {
    const liste = document.getElementById('liste-panier');
    liste.innerHTML = '';
    panier.forEach((item, index) => {
        const li = document.createElement('li');
        const texte = document.createElement('span');
        const bouton = document.createElement('button');
        const nomAvecChoix = item.choix ? `${item.nom} (${item.choix})` : item.nom;
        if (item.quantite > 1) {
            texte.textContent = `${nomAvecChoix} x${item.quantite} - ${item.sousTotal}\u20ac`;
        } else {
            texte.textContent = `${nomAvecChoix} - ${item.prix}\u20ac`;
        }
        bouton.type = 'button';
        bouton.className = 'panier-supprimer';
        bouton.textContent = 'Retirer';
        bouton.addEventListener('click', () => retirerDuPanier(index));
        li.appendChild(texte);
        li.appendChild(bouton);
        liste.appendChild(li);
    });
    document.getElementById('total').textContent = total;
}
async function estConnecte() {
    try {
        const reponse = await fetch('session_status.php', { cache: 'no-store' });
        if (!reponse.ok) return false;
        const donnees = await reponse.json();
        return Boolean(donnees.connected);
    } catch (e) {
        return false;
    }
}
async function validerCommande() {
    const connecte = await estConnecte();
    if (!connecte) {
        alert('Veuillez vous connecter pour valider votre commande.');
        window.location.href = 'Connexion.php';
        return;
    }
    if (panier.length === 0) {
        alert('Votre panier est vide.');
        return;
    }
    const totauxParMenu = {};
    panier.forEach(item => {
        const cle = item.nom;
        if (!totauxParMenu[cle]) {
            totauxParMenu[cle] = { nom: item.nom, minimum: item.minimum || 0, total: 0 };
        }
        totauxParMenu[cle].total += item.quantite;
        totauxParMenu[cle].minimum = Math.max(totauxParMenu[cle].minimum, item.minimum || 0);
    });
    const nonConformes = Object.values(totauxParMenu).filter(item => item.minimum && item.total < item.minimum);
    if (nonConformes.length > 0) {
        const messages = nonConformes.map(item => `${item.nom} (minimum ${item.minimum} personnes, total ${item.total})`).join(', ');
        alert(`Impossible de valider la commande : le minimum de personnes n\'est pas atteint pour ${messages}.`);
        return;
    }
    localStorage.setItem('panierCommande', JSON.stringify(panier));
    localStorage.setItem('totalCommande', String(total));
    window.location.href = 'commande.php';
}
function filtrerMenus() {
    const selectPrix = document.getElementById('filtre-prix');
    const selectPrixMax = document.getElementById('filtre-prix-max');
    const selectRegime = document.getElementById('filtre-regime');
    const selectMinimum = document.getElementById('filtre-minimum');
    const selectTheme = document.getElementById('filtre-theme');
    const listeMenus = document.getElementById('liste-menus');
    if (!selectPrix || !selectPrixMax || !selectRegime || !selectMinimum || !selectTheme || !listeMenus) return;
    const elements = Array.from(listeMenus.querySelectorAll('.element'));
    const appliquerFiltres = () => {
        const valeurPrix = selectPrix.value;
        const valeurPrixMax = selectPrixMax.value;
        const valeurRegime = selectRegime.value.toLowerCase();
        const valeurMinimum = selectMinimum.value;
        const valeurTheme = selectTheme.value;
        elements.forEach(element => {
            const texte = element.textContent.toLowerCase();
            const prixElement = Number(element.getAttribute('data-prix'));
            const regimes = (element.dataset.regime || '').split('|').map(partie => partie.trim().toLowerCase()).filter(Boolean);
            const theme = (element.dataset.theme || '').toLowerCase();
            const minimumTexte = element.querySelector('.menu-minimum')?.textContent || '';
            const minimumNombre = Number((minimumTexte.match(/\d+/) || [0])[0]);
            let correspondPrix = true;
            let correspondPrixMax = true;
            let correspondRegime = true;
            let correspondMinimum = true;
            let correspondTheme = true;
            if (valeurPrix !== 'tout') {
                const [min, max] = valeurPrix.split('-').map(Number);
                correspondPrix = prixElement >= min && prixElement <= max;
            }
            if (valeurPrixMax !== 'tout') {
                correspondPrixMax = prixElement <= Number(valeurPrixMax);
            }
            if (valeurRegime !== 'tout') {
                correspondRegime = regimes.includes(valeurRegime);
            }
            if (valeurTheme !== 'tout') {
                correspondTheme = theme === valeurTheme;
            }
            if (valeurMinimum !== 'tout') {
                const valeurMin = Number(valeurMinimum);
                if (valeurMin === 2) {
                    correspondMinimum = minimumNombre === 2;
                } else {
                    correspondMinimum = minimumNombre <= valeurMin;
                }
            }
            element.style.display = correspondPrix && correspondPrixMax && correspondRegime && correspondMinimum && correspondTheme ? '' : 'none';
        });
    };
    selectPrix.addEventListener('change', appliquerFiltres);
    selectPrixMax.addEventListener('change', appliquerFiltres);
    selectRegime.addEventListener('change', appliquerFiltres);
    selectMinimum.addEventListener('change', appliquerFiltres);
    selectTheme.addEventListener('change', appliquerFiltres);
    appliquerFiltres();
}
document.addEventListener('DOMContentLoaded', () => {
    filtrerMenus();
    initialiserModalMenus();
});
function initialiserModalMenus() {
    const modal = document.getElementById('fenetre-menu');
    const boutonFermer = document.getElementById('fenetre-menu-fermer');
    const titre = document.getElementById('fenetre-menu-titre');
    const regime1 = document.getElementById('fenetre-regime-1');
    const regime2 = document.getElementById('fenetre-regime-2');
    const entree1 = document.getElementById('fenetre-entree-1');
    const plat1 = document.getElementById('fenetre-plat-1');
    const dessert1 = document.getElementById('fenetre-dessert-1');
    const entree2 = document.getElementById('fenetre-entree-2');
    const plat2 = document.getElementById('fenetre-plat-2');
    const dessert2 = document.getElementById('fenetre-dessert-2');
    const colonne1 = document.getElementById('fenetre-colonne-1');
    const colonne2 = document.getElementById('fenetre-colonne-2');
    const allergenesPlat1 = document.getElementById('fenetre-allergenes-plat-1');
    const allergenesPlat2 = document.getElementById('fenetre-allergenes-plat-2');
    const boutonChoix1 = document.getElementById('fenetre-choix-1');
    const boutonChoix2 = document.getElementById('fenetre-choix-2');
    const stockEl = document.getElementById('fenetre-menu-stock');
    if (!modal || !boutonFermer || !titre || !regime1 || !regime2 || !entree1 || !plat1 || !dessert1 || !entree2 || !plat2 || !dessert2 || !allergenesPlat1 || !allergenesPlat2 || !boutonChoix1 || !boutonChoix2 || !colonne1 || !colonne2) return;
    const extraireChoix = (valeur) => {
        return (valeur || '').split('|').map(partie => partie.trim()).filter(Boolean);
    };
    let menuActuel = null;
    const extraireMinimum = (item) => {
        const texte = item.querySelector('.menu-minimum')?.textContent || '';
        const nombre = texte.match(/\d+/);
        return nombre ? Number(nombre[0]) : 1;
    };
    const extrairePrix = (item) => {
        const prixAttr = item.getAttribute('data-prix');
        if (prixAttr) return Number(prixAttr);
        const texte = item.querySelector('p')?.textContent || '';
        const nombre = texte.match(/\d+/);
        return nombre ? Number(nombre[0]) : 0;
    };
    const ouvrirModal = (item) => {
        const nom = item.querySelector('h3')?.textContent || 'Menu';
        const regimes = extraireChoix(item.dataset.regime);
        const entrees = extraireChoix(item.dataset.entrees);
        const plats = extraireChoix(item.dataset.plats);
        const desserts = extraireChoix(item.dataset.desserts);
        const allergenesPlats = extraireChoix(item.dataset.allergenesPlats);
        const regimeChoix1 = regimes[0] || regimes[1] || 'Non précisé';
        const regimeChoix2 = regimes[1] || regimes[0] || 'Non précisé';
        const filtreRegime = document.getElementById('filtre-regime')?.value.toLowerCase() || 'tout';
        const masqueChoix1 = filtreRegime !== 'tout' && regimeChoix1.toLowerCase() !== filtreRegime;
        const masqueChoix2 = filtreRegime !== 'tout' && regimeChoix2.toLowerCase() !== filtreRegime;
        titre.textContent = nom;
        regime1.textContent = `Régime : ${regimeChoix1}`;
        regime2.textContent = `Régime : ${regimeChoix2}`;
        entree1.textContent = entrees[0] || '—';
        plat1.textContent = plats[0] || '—';
        dessert1.textContent = desserts[0] || '—';
        entree2.textContent = entrees[1] || '—';
        plat2.textContent = plats[1] || '—';
        dessert2.textContent = desserts[1] || '—';
        allergenesPlat1.textContent = `Allergènes : ${allergenesPlats[0] || 'aucun'}`;
        allergenesPlat2.textContent = `Allergènes : ${allergenesPlats[1] || 'aucun'}`;
        colonne1.style.display = masqueChoix1 ? 'none' : '';
        colonne2.style.display = masqueChoix2 ? 'none' : '';
        const stock = item.getAttribute('data-stock');
        if (stockEl) stockEl.textContent = `Stock restant pour ce menu : ${stock}`;
        menuActuel = {
            nom,
            prix: extrairePrix(item),
            minimum: extraireMinimum(item)
        };
        modal.classList.add('actif');
        modal.setAttribute('aria-hidden', 'false');
    };
    const fermerModal = () => {
        modal.classList.remove('actif');
        modal.setAttribute('aria-hidden', 'true');
        menuActuel = null;
        if (stockEl) stockEl.textContent = '';
    };
    document.querySelectorAll('.bouton-voir-menu').forEach(bouton => {
        bouton.addEventListener('click', () => {
            const item = bouton.closest('.element');
            if (item) ouvrirModal(item);
        });
    });
    boutonFermer.addEventListener('click', fermerModal);
    boutonChoix1.addEventListener('click', () => {
        if (menuActuel) {
            ajouterAuPanier(menuActuel.nom, menuActuel.prix, menuActuel.minimum, 1);
            fermerModal();
        }
    });
    boutonChoix2.addEventListener('click', () => {
        if (menuActuel) {
            ajouterAuPanier(menuActuel.nom, menuActuel.prix, menuActuel.minimum, 2);
            fermerModal();
        }
    });
}