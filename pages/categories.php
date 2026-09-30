<?php
// ============================================================
//  EcoMarket – Page Catégories
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Catégories';
$pdo = getPDO();

$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) AS nb_produits
    FROM categories c
    LEFT JOIN produits p ON p.categorie_id = c.id
    GROUP BY c.id
    ORDER BY c.nom
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="<?= SITE_URL ?>">Accueil</a> › Catégories</div>
        <h1>Nos Catégories</h1>
        <p>Explorez notre univers par thème</p>
    </div>
</div>

<section class="section">
<div class="container">
    <div class="categories-grid" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr));">
        <?php foreach ($categories as $cat): ?>
        <a href="produits.php?categorie=<?= $cat['id'] ?>" class="cat-card" style="padding:2.5rem 1.5rem;">
            <div class="cat-icon" style="font-size:4rem;"><?= e($cat['icone']) ?></div>
            <div class="cat-name" style="font-size:1.2rem;margin-bottom:.5rem;"><?= e($cat['nom']) ?></div>
            <?php if ($cat['description']): ?>
            <p style="font-size:.85rem;color:var(--gray-600);margin-bottom:.75rem;"><?= e($cat['description']) ?></p>
            <?php endif; ?>
            <div class="cat-count"><?= $cat['nb_produits'] ?> produit<?= $cat['nb_produits'] > 1 ? 's' : '' ?></div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
