<?php // includes/footer.php ?>
</main><!-- /.main-content -->

<!-- ======= FOOTER ======= -->
<footer class="footer">
    <div class="container footer-grid">
        <div class="footer-col">
            <div class="footer-logo">🌿 EcoMarket</div>
            <p class="footer-desc">La marketplace tunisienne dédiée aux produits écologiques, artisanaux et locaux.</p>
        </div>
        <div class="footer-col">
            <h4>Navigation</h4>
            <ul>
                <li><a href="<?= SITE_URL ?>/index.php">Accueil</a></li>
                <li><a href="<?= SITE_URL ?>/pages/produits.php">Produits</a></li>
                <li><a href="<?= SITE_URL ?>/pages/categories.php">Catégories</a></li>
                <li><a href="<?= SITE_URL ?>/pages/panier.php">Panier</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Mon Compte</h4>
            <ul>
                <li><a href="<?= SITE_URL ?>/pages/login.php">Connexion</a></li>
                <li><a href="<?= SITE_URL ?>/pages/inscription.php">Inscription</a></li>
                <li><a href="<?= SITE_URL ?>/pages/commandes.php">Mes Commandes</a></li>
                <li><a href="<?= SITE_URL ?>/pages/expeditions.php">Suivi Livraison</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Administration</h4>
            <ul>
                <?php if (isAdmin()): ?>
                <li><a href="<?= SITE_URL ?>/admin/produits.php">Gérer Produits</a></li>
                <li><a href="<?= SITE_URL ?>/admin/clients.php">Gérer Clients</a></li>
                <li><a href="<?= SITE_URL ?>/admin/commandes.php">Gérer Commandes</a></li>
                <li><a href="<?= SITE_URL ?>/admin/rapports.php">Rapports</a></li>
                <li><a href="<?= SITE_URL ?>/admin/logout.php" style="color:#e74c3c;">Déconnexion admin</a></li>
                <?php else: ?>
                <li><a href="<?= SITE_URL ?>/admin/login.php"> Accès administrateur</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <p>© <?= date('Y') ?> EcoMarket – Consommez responsable, vivez durable </p>
        </div>
    </div>
</footer>

</body>
</html>
