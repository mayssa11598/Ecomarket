<?php
// ============================================================
//  EcoMarket – Page d'accueil
// ============================================================
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Accueil';
$pdo = getPDO();

// Produits en vedette (max 6)
$stmt = $pdo->prepare("
    SELECT p.*, c.nom AS cat_nom
    FROM produits p
    JOIN categories c ON p.categorie_id = c.id
    WHERE p.en_vedette = 1 AND p.stock > 0
    LIMIT 6
");
$stmt->execute();
$vedettes = $stmt->fetchAll();

// Catégories avec comptage produits
$cats = $pdo->query("
    SELECT c.*, COUNT(p.id) AS nb_produits
    FROM categories c
    LEFT JOIN produits p ON p.categorie_id = c.id
    GROUP BY c.id
    ORDER BY nb_produits DESC
    LIMIT 5
")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- ======= HERO ======= -->
<section class="hero">
    <div class="container hero-content">
        <p style="font-size:.9rem;letter-spacing:.15em;text-transform:uppercase;opacity:.8;margin-bottom:.5rem;">
             La marketplace écoresponsable
        </p>
        <h1>Consommez responsable,<br>vivez durable</h1>
        <p>Découvrez nos produits bio, artisanaux et locaux,<br>sélectionnés avec soin pour vous et la planète.</p>
        <div class="hero-btns">
            <a href="pages/produits.php" class="btn-hero-primary">Explorer les produits →</a>
            <a href="pages/categories.php" class="btn-hero-outline">Voir les catégories</a>
        </div>
    </div>
</section>

<!-- ======= STATS RAPIDES ======= -->
<section style="background:var(--green-xlight);padding:2rem 0;">
    <div class="container">
        <div class="stats-grid" style="margin-bottom:0;">
            <div class="stat-card">
                <div class="stat-value">100%</div>
                <div class="stat-label">Produits naturels certifiés</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">500+</div>
                <div class="stat-label">Artisans et producteurs locaux</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">0</div>
                <div class="stat-label">Plastique dans nos emballages</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">48h</div>
                <div class="stat-label">Livraison express en Tunisie</div>
            </div>
        </div>
    </div>
</section>

<!-- ======= PRODUITS EN VEDETTE ======= -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2> Produits en vedette</h2>
            <p>Notre sélection de coups de cœur écoresponsables</p>
            <div class="section-divider"></div>
        </div>

        <?php if (empty($vedettes)): ?>
            <div class="empty-state">
                <div class="empty-icon">🌱</div>
                <h3>Bientôt disponible</h3>
                <p>Nos produits vedettes arrivent sous peu.</p>
            </div>
        <?php else: ?>
        <div class="products-grid">
            <?php foreach ($vedettes as $p): ?>
            <div class="card product-card">
                <?php if ($p['en_vedette']): ?>
                    <span class="badge-vedette"> Vedette</span>
                <?php endif; ?>
                <a href="pages/produit_detail.php?id=<?= $p['id'] ?>">
                <img src="<?= e(imgUrl($p['image'] ?? '')) ?>"
                     alt="<?= e($p['nom']) ?>"
                     class="product-img"
                     style="width:100%;height:200px;object-fit:cover;"
                     onerror="this.src='https://placehold.co/400x300/d5f5e3/2ecc71?text=EcoMarket'">
            </a>
                <div class="card-body">
                    <div class="product-cat"><?= e($p['cat_nom']) ?></div>
                    <h3 class="product-name">
                        <a href="pages/produit_detail.php?id=<?= $p['id'] ?>"><?= e($p['nom']) ?></a>
                    </h3>
                    <p class="product-desc"><?= e($p['description']) ?></p>
                    <div class="product-footer">
                        <span class="product-price"><?= prix((float)$p['prix']) ?></span>
                        <span class="product-stock">Stock : <?= (int)$p['stock'] ?></span>
                    </div>
                    <!-- Formulaire Ajout Panier (SANS JS) -->
                    <form method="POST" action="pages/panier.php" style="margin-top:.75rem;">
                        <input type="hidden" name="action"     value="ajouter">
                        <input type="hidden" name="produit_id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-primary" style="width:100%;">
                            🛒 Ajouter au panier
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:1rem;">
            <a href="pages/produits.php" class="btn btn-outline">Voir tous les produits →</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ======= CATÉGORIES ======= -->
<section class="section" style="background:var(--gray-100);padding:3rem 0;">
    <div class="container">
        <div class="section-header">
            <h2> Nos catégories</h2>
            <p>Explorez notre sélection par univers</p>
            <div class="section-divider"></div>
        </div>
        <div class="categories-grid">
            <?php foreach ($cats as $cat): ?>
            <a href="pages/produits.php?categorie=<?= $cat['id'] ?>" class="cat-card">
                <div class="cat-icon"><?= e($cat['icone']) ?></div>
                <div class="cat-name"><?= e($cat['nom']) ?></div>
                <div class="cat-count"><?= $cat['nb_produits'] ?> produits</div>
            </a>
            <?php endforeach; ?>
            <a href="pages/categories.php" class="cat-card" style="border-style:dashed;">
                <div class="cat-icon">➕</div>
                <div class="cat-name">Toutes les catégories</div>
                <div class="cat-count">Voir tout</div>
            </a>
        </div>
    </div>
</section>

<!-- ======= POURQUOI NOUS ======= -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>Pourquoi EcoMarket ?</h2>
            <p>Notre engagement pour vous et la planète</p>
            <div class="section-divider"></div>
        </div>
        <div class="why-grid">
            <div class="why-card">
                <div class="why-icon">🌱</div>
                <h3>100% Naturel</h3>
                <p>Tous nos produits sont sélectionnés selon des critères stricts de naturalité et de durabilité.</p>
            </div>
            <div class="why-card">
                <div class="why-icon">🏺</div>
                <h3>Artisans Locaux</h3>
                <p>Nous soutenons les artisans tunisiens et promouvons le savoir-faire local traditionnel.</p>
            </div>
            <div class="why-card">
                <div class="why-icon">🚚</div>
                <h3>Livraison Rapide</h3>
                <p>Emballages écoresponsables, livraison en 48h partout en Tunisie.</p>
            </div>
            <div class="why-card">
                <div class="why-icon">🔒</div>
                <h3>Paiement Sécurisé</h3>
                <p>Vos transactions sont protégées par des protocoles de sécurité avancés.</p>
            </div>
            <div class="why-card">
                <div class="why-icon">♻️</div>
                <h3>Zéro Déchet</h3>
                <p>Nous minimisons les emballages et privilégions les matériaux recyclables et réutilisables.</p>
            </div>
        </div>
    </div>
</section>

<!-- ======= BANNIÈRE ENGAGEMENT ======= -->
<section style="background:linear-gradient(135deg,#1a472a,#2ecc71);color:white;padding:3rem 0;text-align:center;">
    <div class="container">
        <h2 style="font-family:var(--font-head);font-size:1.8rem;margin-bottom:1rem;">
             Chaque achat est un acte engagé
        </h2>
        <p style="opacity:.9;max-width:600px;margin:0 auto 1.5rem;">
            En choisissant EcoMarket, vous soutenez l'économie locale, protégez l'environnement
            et encouragez des pratiques de production responsables.
        </p>
        <a href="pages/inscription.php" style="background:white;color:#1a472a;padding:.8rem 2rem;border-radius:50px;font-weight:700;display:inline-block;">
            Rejoindre la communauté →
        </a>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
