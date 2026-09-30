<?php
// ============================================================
//  EcoMarket – Passer une commande
// ============================================================
require_once __DIR__ . '/../includes/config.php';
if (!isLoggedIn()) { redirect(SITE_URL . '/pages/login.php?redirect=panier'); }
$pageTitle = 'Passer une commande';
$pdo = getPDO();

// Charger le client
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([clientId()]);
$client = $stmt->fetch();

$panier = $_SESSION['panier'] ?? [];
if (empty($panier)) {
    flash('panier', 'Votre panier est vide.', 'error');
    redirect(SITE_URL . '/pages/panier.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adresse = trim($_POST['adresse_livraison'] ?? $client['adresse'] ?? '');
    if ($adresse === '') $errors[] = 'L\'adresse de livraison est requise.';

    if (empty($errors)) {
        // Calculer total et créer la commande (transaction)
        $pdo->beginTransaction();
        try {
            $total = 0;
            foreach ($panier as $pid => $item) {
                $total += $item['prix'] * $item['qte'];
            }

            // Insérer la commande
            $stmt = $pdo->prepare("INSERT INTO commandes (client_id, total) VALUES (?, ?)");
            $stmt->execute([clientId(), $total]);
            $cmdId = $pdo->lastInsertId();

            // Insérer les lignes
            foreach ($panier as $pid => $item) {
                $stmt = $pdo->prepare("INSERT INTO commandes_produits (commande_id, produit_id, quantite, prix_unitaire) VALUES (?,?,?,?)");
                $stmt->execute([$cmdId, $pid, $item['qte'], $item['prix']]);
                // Décrémenter le stock
                $pdo->prepare("UPDATE produits SET stock = stock - ? WHERE id = ?")->execute([$item['qte'], $pid]);
            }

            // Créer l'expédition
            $stmt = $pdo->prepare("INSERT INTO expeditions (commande_id, adresse_livraison) VALUES (?,?)");
            $stmt->execute([$cmdId, $adresse]);

            $pdo->commit();
            $_SESSION['panier'] = [];
            flash('commandes', 'Commande #' . $cmdId . ' passée avec succès ! 🎉', 'success');
            redirect(SITE_URL . '/pages/commandes.php');
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Erreur lors de la commande : ' . $e->getMessage();
        }
    }
}

// Calculer total panier
$total = array_sum(array_map(fn($i) => $i['prix'] * $i['qte'], $panier));

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Finaliser la commande</h1>
    </div>
</div>

<section class="section" style="padding-top:1.5rem;">
<div class="container">
<?php foreach ($errors as $err): ?>
    <div class="alert alert-error">❌ <?= e($err) ?></div>
<?php endforeach; ?>

<div class="cart-layout">
    <!-- Récap panier -->
    <div class="card">
        <div class="card-body">
            <h3 style="font-family:var(--font-head);margin-bottom:1rem;">📦 Récapitulatif</h3>
            <?php foreach ($panier as $pid => $item): ?>
            <div style="display:flex;justify-content:space-between;padding:.6rem 0;border-bottom:1px solid var(--gray-100);">
                <span><?= e($item['nom']) ?> × <?= $item['qte'] ?></span>
                <strong><?= prix($item['prix'] * $item['qte']) ?></strong>
            </div>
            <?php endforeach; ?>
            <div style="display:flex;justify-content:space-between;padding:.75rem 0;font-size:1.1rem;font-weight:700;color:var(--green-dark);">
                <span>Total</span>
                <span><?= prix($total) ?></span>
            </div>
        </div>
    </div>

    <!-- Formulaire commande -->
    <div class="form-card" style="margin:0;">
        <h3 class="form-title">📍 Adresse de livraison</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Adresse complète *</label>
                <textarea name="adresse_livraison" class="form-control" rows="4" required
                          placeholder="Ex: 12 Rue de la Paix, 1000 Tunis"><?= e($client['adresse'] ?? '') ?></textarea>
            </div>
            <div style="background:var(--green-xlight);padding:1rem;border-radius:var(--radius);margin-bottom:1rem;font-size:.9rem;">
                🚚 <strong>Livraison gratuite</strong> en 48h partout en Tunisie<br>
                📦 Emballage 100% recyclable
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;font-size:1rem;padding:.9rem;">
                🛍️ Confirmer la commande — <?= prix($total) ?>
            </button>
        </form>
        <a href="panier.php" class="btn btn-outline" style="width:100%;display:block;text-align:center;margin-top:.75rem;">
            ← Retour au panier
        </a>
    </div>
</div>
</div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
