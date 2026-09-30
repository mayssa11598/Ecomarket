<?php
// ============================================================
//  EcoMarket – Panier (SESSION PHP, sans JavaScript)
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Mon Panier';
$pdo = getPDO();

// ── Traitement des actions panier ──
$action = $_POST['action'] ?? ($_GET['action'] ?? '');

if ($action === 'ajouter' && isset($_POST['produit_id'])) {
    $pid = (int)$_POST['produit_id'];
    $qty = max(1, (int)($_POST['quantite'] ?? 1));
    // Vérifier stock
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ?");
    $stmt->execute([$pid]);
    $prod = $stmt->fetch();
    if ($prod && $prod['stock'] >= $qty) {
        if (!isset($_SESSION['panier'][$pid])) {
            $_SESSION['panier'][$pid] = ['qte' => 0, 'nom' => $prod['nom'], 'prix' => $prod['prix'], 'image' => $prod['image'] ?? ''];
        }
        $_SESSION['panier'][$pid]['qte'] += $qty;
        flash('panier', 'Produit ajouté au panier !', 'success');
    } else {
        flash('panier', 'Stock insuffisant.', 'error');
    }
    redirect(SITE_URL . '/pages/panier.php');
}

if ($action === 'supprimer' && isset($_GET['pid'])) {
    $pid = (int)$_GET['pid'];
    unset($_SESSION['panier'][$pid]);
    flash('panier', 'Produit retiré du panier.', 'success');
    redirect(SITE_URL . '/pages/panier.php');
}

if ($action === 'vider') {
    $_SESSION['panier'] = [];
    flash('panier', 'Panier vidé.', 'success');
    redirect(SITE_URL . '/pages/panier.php');
}

if ($action === 'mettre_a_jour' && isset($_POST['quantites'])) {
    foreach ($_POST['quantites'] as $pid => $qty) {
        $pid = (int)$pid;
        $qty = max(0, (int)$qty);
        if ($qty === 0) {
            unset($_SESSION['panier'][$pid]);
        } elseif (isset($_SESSION['panier'][$pid])) {
            $_SESSION['panier'][$pid]['qte'] = $qty;
        }
    }
    flash('panier', 'Panier mis à jour.', 'success');
    redirect(SITE_URL . '/pages/panier.php');
}

// ── Calcul total ──
$panier = $_SESSION['panier'] ?? [];
$total  = 0;
foreach ($panier as $item) {
    $total += $item['prix'] * $item['qte'];
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="<?= SITE_URL ?>">Accueil</a> › Panier</div>
        <h1>🛒 Mon Panier</h1>
        <p><?= count($panier) ?> article<?= count($panier) > 1 ? 's' : '' ?></p>
    </div>
</div>

<section class="section" style="padding-top:1.5rem;">
<div class="container">
<?= flash('panier') ?>

<?php if (empty($panier)): ?>
<div class="empty-state">
    <div class="empty-icon">🛒</div>
    <h3>Votre panier est vide</h3>
    <p>Découvrez nos produits et ajoutez-en à votre panier.</p>
    <a href="produits.php" class="btn btn-primary">Explorer les produits</a>
</div>
<?php else: ?>

<div class="cart-layout">
    <!-- Articles -->
    <div>
        <div class="card">
            <form method="POST" action="">
                <input type="hidden" name="action" value="mettre_a_jour">
                <?php foreach ($panier as $pid => $item): ?>
                <div class="cart-item">
                    <div class="cart-item-img">
                        <img src="<?= e(imgUrl($item['image'] ?? '')) ?>"
                             alt="<?= e($item['nom']) ?>"
                             style="width:70px;height:70px;object-fit:cover;border-radius:var(--radius);">
                    </div>
                    <div class="cart-item-info">
                        <div class="cart-item-name"><?= e($item['nom']) ?></div>
                        <div class="cart-item-price"><?= prix((float)$item['prix']) ?></div>
                    </div>
                    <div class="qty-control" style="margin:0 1rem;">
                        <input type="number" name="quantites[<?= $pid ?>]"
                               value="<?= (int)$item['qte'] ?>" min="0" max="99" style="width:65px;">
                    </div>
                    <div style="font-weight:700;color:var(--green-dark);min-width:80px;text-align:right;">
                        <?= prix((float)$item['prix'] * $item['qte']) ?>
                    </div>
                    <a href="?action=supprimer&pid=<?= $pid ?>"
                       style="color:#e74c3c;margin-left:1rem;font-size:1.2rem;"
                       title="Supprimer">✕</a>
                </div>
                <?php endforeach; ?>
                <div style="padding:1rem;display:flex;gap:1rem;justify-content:flex-end;border-top:1px solid var(--gray-100);">
                    <button type="submit" class="btn btn-outline btn-sm">↻ Mettre à jour</button>
                </div>
            </form>
        </div>
        <div style="margin-top:1rem;">
            <a href="?action=vider" class="btn btn-secondary btn-sm"
               onclick="return confirm('Vider le panier ?')">Vider le panier</a>
        </div>
    </div>

    <!-- Récapitulatif -->
    <div class="order-summary">
        <h3>Récapitulatif</h3>
        <div class="summary-row">
            <span>Sous-total</span>
            <span><?= prix($total) ?></span>
        </div>
        <div class="summary-row">
            <span>Livraison</span>
            <span style="color:var(--green-dark);">Gratuite</span>
        </div>
        <div class="summary-row summary-total">
            <span>Total</span>
            <span><?= prix($total) ?></span>
        </div>

        <?php if (isLoggedIn()): ?>
            <form method="POST" action="commander.php" style="margin-top:1.25rem;">
                <input type="hidden" name="from_panier" value="1">
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    Passer la commande
                </button>
            </form>
        <?php else: ?>
            <a href="login.php?redirect=panier" class="btn btn-primary" style="width:100%;display:block;text-align:center;margin-top:1.25rem;">
                 Se connecter pour commander
            </a>
        <?php endif; ?>
        <a href="produits.php" class="btn btn-outline" style="width:100%;display:block;text-align:center;margin-top:.75rem;">
            ← Continuer mes achats
        </a>
    </div>
</div>
<?php endif; ?>
</div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
