<?php
// includes/header.php
require_once __DIR__ . '/config.php';
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' – ' : '' ?><?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>

<!-- ======= BARRE ADMIN (visible uniquement pour les admins) ======= -->
<?php if (isAdmin()): ?>
<div style="background:#1a2620;color:rgba(255,255,255,.85);font-size:.82rem;padding:.45rem 0;">
    <div class="container" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;">
        <span>⚙️ Mode <strong style="color:var(--green);">Administrateur</strong> — <?= e($_SESSION['client_nom'] ?? '') ?></span>
        <div style="display:flex;gap:1rem;">
            <a href="<?= SITE_URL ?>/admin/produits.php"   style="color:rgba(255,255,255,.7);"> Produits</a>
            <a href="<?= SITE_URL ?>/admin/clients.php"    style="color:rgba(255,255,255,.7);"> Clients</a>
            <a href="<?= SITE_URL ?>/admin/commandes.php"  style="color:rgba(255,255,255,.7);"> Commandes</a>
            <a href="<?= SITE_URL ?>/admin/rapports.php"   style="color:rgba(255,255,255,.7);"> Rapports</a>
            <a href="<?= SITE_URL ?>/admin/logout.php"     style="color:#e74c3c;">Déconnexion</a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ======= BARRE DE NAVIGATION ======= -->
<header class="navbar<?= isAdmin() && strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? ' navbar-hidden' : '' ?>">
    <div class="container nav-inner">

        <!-- LEFT : logo + liens -->
        <div class="nav-left">
            <a href="<?= SITE_URL ?>/index.php" class="logo">
                <span class="logo-leaf">🌿</span>
                <span class="logo-text">Eco<strong>Market</strong></span>
            </a>

            <nav class="nav-links">
                <a href="<?= SITE_URL ?>/index.php" class="<?= $currentPage==='index' ? 'active':'' ?>">Accueil</a>
                <a href="<?= SITE_URL ?>/pages/produits.php" class="<?= $currentPage==='produits' ? 'active':'' ?>">Produits</a>
                <a href="<?= SITE_URL ?>/pages/categories.php" class="<?= $currentPage==='categories' ? 'active':'' ?>">Catégories</a>

                <?php if (isLoggedIn()): ?>
                    <a href="<?= SITE_URL ?>/pages/commandes.php">Mes Commandes</a>
                    <a href="<?= SITE_URL ?>/pages/expeditions.php">Expéditions</a>
                <?php endif; ?>
            </nav>
        </div>

        <!-- CENTER : recherche -->
        <div class="nav-center">
            <form method="GET" action="<?= SITE_URL ?>/pages/produits.php" class="search-form">
                <input type="text" name="q" placeholder="Rechercher…"
                       value="<?= e($_GET['q'] ?? '') ?>" class="search-input">
                <button type="submit" class="search-btn">🔍</button>
            </form>
        </div>

        <!-- RIGHT : actions -->
        <div class="nav-right">

            <a href="<?= SITE_URL ?>/pages/panier.php" class="btn-cart">
                🛒<span class="cart-badge"><?= cartCount() ?></span>
            </a>

            <?php if (isLoggedIn()): ?>
                <div class="nav-user">
                    <a href="<?= SITE_URL ?>/pages/compte.php" class="btn-compte">
                        👤 <?= e($_SESSION['client_nom'] ?? 'Mon compte') ?>
                    </a>
                    <a href="<?= SITE_URL ?>/pages/logout.php" class="btn-logout">Déconnexion</a>
                </div>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/pages/login.php" class="btn btn-outline-sm">Connexion</a>
                <a href="<?= SITE_URL ?>/pages/inscription.php" class="btn btn-primary-sm">S'inscrire</a>
            <?php endif; ?>

        </div>

    </div>
</header>


<main class="main-content">
