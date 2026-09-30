<?php
// ============================================================
//  EcoMarket – Détail produit
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$pdo = getPDO();
$id  = (int)($_GET['id'] ?? 0);
if (!$id) { redirect(SITE_URL . '/pages/produits.php'); }

$stmt = $pdo->prepare("
    SELECT p.*, c.nom AS cat_nom, c.icone AS cat_icone
    FROM produits p
    JOIN categories c ON p.categorie_id = c.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { redirect(SITE_URL . '/pages/produits.php'); }

$pageTitle = $p['nom'];
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?= SITE_URL ?>">Accueil</a> ›
            <a href="<?= SITE_URL ?>/pages/produits.php">Produits</a> ›
            <?= e($p['nom']) ?>
        </div>
    </div>
</div>

<section class="section" style="padding-top:2rem;">
<div class="container">
<?= flash('panier') ?>

<div class="product-detail">
    <!-- Image -->
    <div class="detail-img">
        <img src="<?= e(imgUrl($p['image'] ?? '')) ?>"
             alt="<?= e($p['nom']) ?>"
             style="width:100%;height:100%;object-fit:cover;"
             onerror="this.src='https://placehold.co/600x400/d5f5e3/2ecc71?text=EcoMarket'">
    </div>

    <!-- Infos -->
    <div>
        <div class="detail-cat">
            <?= e($p['cat_icone']) ?> <?= e($p['cat_nom']) ?>
        </div>
        <h1 class="detail-name"><?= e($p['nom']) ?></h1>
        <div class="detail-price"><?= prix((float)$p['prix']) ?></div>

        <?php if ($p['stock'] > 0): ?>
            <div class="detail-stock">
                 En stock — <?= (int)$p['stock'] ?> disponibles
            </div>
        <?php else: ?>
            <div class="detail-stock" style="color:#e74c3c;"> Rupture de stock</div>
        <?php endif; ?>

        <p class="detail-desc"><?= nl2br(e($p['description'])) ?></p>

        <?php if ($p['stock'] > 0): ?>
        <!-- Formulaire Panier -->
        <form method="POST" action="panier.php" style="display:flex;gap:1rem;align-items:center;margin-bottom:1rem;">
            <input type="hidden" name="action"     value="ajouter">
            <input type="hidden" name="produit_id" value="<?= $p['id'] ?>">
            <div class="qty-control">
                <label style="font-weight:600;font-size:.9rem;">Qté :</label>
                <input type="number" name="quantite" value="1" min="1" max="<?= $p['stock'] ?>">
            </div>
            <button type="submit" class="btn btn-primary">🛒 Ajouter au panier</button>
        </form>

        <!-- Formulaire Commander directement -->
        <?php if (isLoggedIn()): ?>
        <form method="POST" action="commander.php">
            <input type="hidden" name="produit_id" value="<?= $p['id'] ?>">
            <input type="hidden" name="quantite"   value="1">
            <button type="submit" class="btn btn-outline">⚡ Commander maintenant</button>
        </form>
        <?php else: ?>
            <a href="login.php" class="btn btn-outline">Connectez-vous pour commander</a>
        <?php endif; ?>
        <?php endif; ?>

        <div style="margin-top:1.5rem;padding:1rem;background:var(--green-xlight);border-radius:var(--radius);">
            <strong> Livraison</strong> : 48h partout en Tunisie<br>
            <strong> Emballage</strong> : 100% recyclable<br>
            <strong> Retour</strong> : 14 jours satisfait ou remboursé
        </div>
    </div>
</div>
</div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
