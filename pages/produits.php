<?php
// ============================================================
//  EcoMarket – Page Produits (liste + filtres + pagination)
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Produits';
$pdo = getPDO();

// ── Paramètres GET ──
$q        = trim($_GET['q']        ?? '');
$catId    = (int)($_GET['categorie'] ?? 0);
$prixMin  = (float)($_GET['prix_min'] ?? 0);
$prixMax  = (float)($_GET['prix_max'] ?? 0);
$page     = max(1, (int)($_GET['page'] ?? 1));

// ── Construction de la requête ──
$where  = ['1=1'];
$params = [];
if ($q !== '') {
    $where[]      = '(p.nom LIKE :q OR p.description LIKE :q2)';
    $params[':q']  = "%$q%";
    $params[':q2'] = "%$q%";
}
if ($catId > 0) {
    $where[]          = 'p.categorie_id = :cat';
    $params[':cat']   = $catId;
}
if ($prixMin > 0) {
    $where[]           = 'p.prix >= :pmin';
    $params[':pmin']   = $prixMin;
}
if ($prixMax > 0) {
    $where[]           = 'p.prix <= :pmax';
    $params[':pmax']   = $prixMax;
}
$whereSQL = implode(' AND ', $where);

// Comptage total
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM produits p WHERE $whereSQL");
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();

$pg = paginate($total, $page);

// Produits paginés
$stmt = $pdo->prepare("
    SELECT p.*, c.nom AS cat_nom
    FROM produits p
    JOIN categories c ON p.categorie_id = c.id
    WHERE $whereSQL
    ORDER BY p.en_vedette DESC, p.created_at DESC
    LIMIT " . PER_PAGE . " OFFSET " . $pg['offset']
);
$stmt->execute($params);
$produits = $stmt->fetchAll();

// Catégories pour le filtre
$categories = $pdo->query("SELECT * FROM categories ORDER BY nom")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<!-- En-tête de page -->
<div class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="<?= SITE_URL ?>">Accueil</a> › Produits</div>
        <h1>🛍️ Nos Produits</h1>
        <p><?= $pg['total'] ?> produit<?= $pg['total'] > 1 ? 's' : '' ?> trouvé<?= $pg['total'] > 1 ? 's' : '' ?>
           <?= $q ? 'pour "<strong>' . e($q) . '</strong>"' : '' ?></p>
    </div>
</div>

<section class="section" style="padding-top:1.5rem;">
<div class="container">

<?= flash('panier') ?>

<!-- ── Barre de filtres ── -->
<form method="GET" action="" class="filter-bar">
    <div class="filter-group">
        <label>Catégorie</label>
        <select name="categorie">
            <option value="">Toutes les catégories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $catId == $cat['id'] ? 'selected' : '' ?>>
                <?= e($cat['icone']) ?> <?= e($cat['nom']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-group">
        <label>Prix min (DT)</label>
        <input type="number" name="prix_min" min="0" step="0.5" value="<?= e((string)$prixMin) ?>" placeholder="0">
    </div>
    <div class="filter-group">
        <label>Prix max (DT)</label>
        <input type="number" name="prix_max" min="0" step="0.5" value="<?= e((string)$prixMax) ?>" placeholder="Ex: 100">
    </div>
    <?php if ($q !== ''): ?>
        <input type="hidden" name="q" value="<?= e($q) ?>">
    <?php endif; ?>
    <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
    <a href="produits.php" class="btn btn-secondary btn-sm">Réinitialiser</a>
</form>

<!-- ── Grille de produits ── -->
<?php if (empty($produits)): ?>
    <div class="empty-state">
        <div class="empty-icon">🔍</div>
        <h3>Aucun produit trouvé</h3>
        <p>Modifiez vos filtres ou votre recherche.</p>
        <a href="produits.php" class="btn btn-primary">Voir tous les produits</a>
    </div>
<?php else: ?>
    <div class="products-grid">
        <?php foreach ($produits as $p): ?>
        <div class="card product-card">
            <?php if ($p['en_vedette']): ?>
                <span class="badge-vedette"> Vedette</span>
            <?php endif; ?>
            <a href="produit_detail.php?id=<?= $p['id'] ?>">
                <img src="<?= e(imgUrl($p['image'] ?? '')) ?>"
                     alt="<?= e($p['nom']) ?>"
                     class="product-img"
                     style="width:100%;height:200px;object-fit:cover;"
                     onerror="this.src='https://placehold.co/400x300/d5f5e3/2ecc71?text=EcoMarket'">
            </a>
            <div class="card-body">
                <div class="product-cat"><?= e($p['cat_nom']) ?></div>
                <h3 class="product-name">
                    <a href="produit_detail.php?id=<?= $p['id'] ?>"><?= e($p['nom']) ?></a>
                </h3>
                <p class="product-desc"><?= e($p['description']) ?></p>
                <div class="product-footer">
                    <span class="product-price"><?= prix((float)$p['prix']) ?></span>
                    <?php if ($p['stock'] == 0): ?>
                        <span style="color:#e74c3c;font-size:.8rem;">Rupture</span>
                    <?php else: ?>
                        <span class="product-stock">Stock : <?= $p['stock'] ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($p['stock'] > 0): ?>
                <form method="POST" action="panier.php" style="margin-top:.75rem;">
                    <input type="hidden" name="action"     value="ajouter">
                    <input type="hidden" name="produit_id" value="<?= $p['id'] ?>">
                    <button type="submit" class="btn btn-primary" style="width:100%;">🛒 Ajouter au panier</button>
                </form>
                <?php else: ?>
                    <button class="btn btn-secondary" style="width:100%;margin-top:.75rem;" disabled>Indisponible</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Pagination ── -->
    <?php if ($pg['pages'] > 1): ?>
    <div class="pagination">
        <?php
        // Construire les paramètres URL sans 'page'
        $qParams = array_filter(['q' => $q, 'categorie' => $catId ?: '', 'prix_min' => $prixMin ?: '', 'prix_max' => $prixMax ?: '']);
        $qStr    = http_build_query($qParams);
        $base    = 'produits.php?' . ($qStr ? $qStr . '&' : '') . 'page=';
        ?>
        <?php if ($pg['page'] > 1): ?>
            <a href="<?= $base . ($pg['page']-1) ?>">‹ Précédent</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $pg['pages']; $i++): ?>
            <?php if ($i == $pg['page']): ?>
                <span class="current"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= $base . $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($pg['page'] < $pg['pages']): ?>
            <a href="<?= $base . ($pg['page']+1) ?>">Suivant ›</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
<?php endif; ?>

</div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
