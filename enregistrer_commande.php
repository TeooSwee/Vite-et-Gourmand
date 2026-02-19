<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
if (!isset($_SESSION['utilisateur_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Connexion requise.']);
    exit;
}
$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides.']);
    exit;
}
$items = $payload['items'] ?? [];
$totalMenu = isset($payload['total']) ? (float) $payload['total'] : 0.0;
$prixLivraison = isset($payload['prix_livraison']) ? (float) $payload['prix_livraison'] : 0.0;
$datePrestation = $payload['date_prestation'] ?? '';
$heureLivraison = $payload['heure_livraison'] ?? '';
$pretMateriel = isset($payload['pret_materiel']) ? (int) $payload['pret_materiel'] : 0;
if (!is_array($items) || empty($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Votre panier est vide.']);
    exit;
}
if ($datePrestation === '' || $heureLivraison === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Date et heure requises.']);
    exit;
}
$dateCommande = new DateTime();
$datePresta = DateTime::createFromFormat('Y-m-d', $datePrestation);
if (!$datePresta) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Date de prestation invalide.']);
    exit;
}
$interval = $dateCommande->diff($datePresta);
if ($interval->invert === 1 || $interval->days < 3) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Merci de choisir une date de prestation au moins 3 jours après la commande.']);
    exit;
}
require 'db.php';
try {
    $pdo->beginTransaction();
    $numeroCommande = uniqid('CMD');
    $dateCommande = date('Y-m-d');
    $nombrePersonne = array_sum(array_map(fn($i) => (int) ($i['quantite'] ?? 0), $items));
    $statut = 'nouvelle';
    $restitutionMateriel = 0;
    $insertCommande = $pdo->prepare('INSERT INTO commande (numero_commande, date_commande, date_prestation, heure_livraison, prix_menu, nombre_personne, prix_livraison, statut, pret_materiel, restitution_materiel, utilisateur_id) VALUES (:numero_commande, :date_commande, :date_prestation, :heure_livraison, :prix_menu, :nombre_personne, :prix_livraison, :statut, :pret_materiel, :restitution_materiel, :utilisateur_id)');
    $insertCommande->execute([
        ':numero_commande' => $numeroCommande,
        ':date_commande' => $dateCommande,
        ':date_prestation' => $datePrestation,
        ':heure_livraison' => $heureLivraison,
        ':prix_menu' => $totalMenu,
        ':nombre_personne' => max(1, $nombrePersonne),
        ':prix_livraison' => $prixLivraison,
        ':statut' => $statut,
        ':pret_materiel' => $pretMateriel,
        ':restitution_materiel' => $restitutionMateriel,
        ':utilisateur_id' => $_SESSION['utilisateur_id']
    ]);
    $selectMenu = $pdo->prepare('SELECT menu_id FROM menu WHERE titre = :titre LIMIT 1');
    $insertMenu = $pdo->prepare('INSERT INTO menu (titre, nombre_personne_minimum, prix_par_personne, regime, description, quantite_restante) VALUES (:titre, :minimum, :prix, :regime, :description, :quantite)');
    $insertLien = $pdo->prepare('INSERT INTO commande_menu (numero_commande, menu_id) VALUES (:numero_commande, :menu_id)');
    $menusRegroupes = [];
    foreach ($items as $item) {
        $titre = trim((string) ($item['nom'] ?? ''));
        if ($titre === '') continue;
        if (!isset($menusRegroupes[$titre])) {
            $menusRegroupes[$titre] = [
                'prix' => (float) ($item['prix'] ?? 0),
                'minimum' => (int) ($item['minimum'] ?? 1),
                'quantite' => 0,
                'choix' => [],
            ];
        }
        $menusRegroupes[$titre]['quantite'] += (int) ($item['quantite'] ?? 1);
        $menusRegroupes[$titre]['choix'][] = $item;
    }
    $menusAjoutes = [];
    foreach ($menusRegroupes as $titre => $menu) {
        $prix = $menu['prix'];
        $minimum = $menu['minimum'];
        $quantiteTotale = $menu['quantite'];
        $selectMenu->execute([':titre' => $titre]);
        $menuId = $selectMenu->fetchColumn();
        if (!$menuId) {
            $insertMenu->execute([
                ':titre' => $titre,
                ':minimum' => $minimum,
                ':prix' => $prix,
                ':regime' => '',
                ':description' => '',
                ':quantite' => 0
            ]);
            $menuId = $pdo->lastInsertId();
        }
        $checkQuantite = $pdo->prepare('SELECT quantite_restante FROM menu WHERE menu_id = :menu_id');
        $checkQuantite->execute([':menu_id' => $menuId]);
        $quantiteRestante = (int) $checkQuantite->fetchColumn();
        if ($quantiteTotale > $quantiteRestante) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "La quantité demandée pour le menu '$titre' dépasse le stock disponible ($quantiteRestante restant)."]);
            exit;
        }
        $prixMenu = $prix * $quantiteTotale;
        if ($quantiteTotale >= $minimum + 5) {
            $prixMenu *= 0.9;
        }
        $totalMenu += $prixMenu;
        $updateQuantite = $pdo->prepare('UPDATE menu SET quantite_restante = GREATEST(quantite_restante - :qte, 0) WHERE menu_id = :menu_id');
        $updateQuantite->execute([':qte' => $quantiteTotale, ':menu_id' => $menuId]);
        if (!isset($menusAjoutes[$menuId])) {
            $insertLien->execute([
                ':numero_commande' => $numeroCommande,
                ':menu_id' => $menuId
            ]);
            $menusAjoutes[$menuId] = true;
        }
    }
    $pdo->commit();
    require 'mailer.php';
    $emailClient = $pdo->prepare('SELECT email, prenom FROM utilisateur WHERE utilisateur_id = :id');
    $emailClient->execute([':id' => $_SESSION['utilisateur_id']]);
    $client = $emailClient->fetch(PDO::FETCH_ASSOC);
    if ($client) {
        $sujet = 'Confirmation de votre commande';
        $message = "Bonjour {$client['prenom']},\n\nVotre commande {$numeroCommande} est confirmée.\n\nPrix menu : {$totalMenu}€\nPrix livraison : {$prixLivraison}€\n\nMerci.";
        envoyerMailSmtp($client['email'], $sujet, $message);
    }
    echo json_encode(['success' => true, 'numero_commande' => $numeroCommande]);
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log('Erreur commande: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

