<?php
// ============================================================
//  EcoMarket – Admin : Rapports & Statistiques
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Admin – Rapports';
$pdo = getPDO();
requireAdmin();

// ── Filtres date ──
$dateDebut = $_GET['date_debut'] ?? date('Y-m-01');
$dateFin   = $_GET['date_fin']   ?? date('Y-m-d');

// ── Rapport 1 : Ventes par catégorie ──
$stmtCat = $pdo->prepare("
    SELECT cat.nom AS categorie, cat.icone,
           COUNT(DISTINCT c.id)   AS nb_commandes,
           SUM(cp.quantite)       AS total_articles,
           SUM(cp.quantite * cp.prix_unitaire) AS chiffre_affaires
    FROM commandes_produits cp
    JOIN commandes c  ON c.id = cp.commande_id
    JOIN produits  p  ON p.id = cp.produit_id
    JOIN categories cat ON cat.id = p.categorie_id
    WHERE c.date_cmd BETWEEN :d1 AND :d2
      AND c.statut NOT IN ('annulee')
    GROUP BY cat.id
    ORDER BY chiffre_affaires DESC
");
$stmtCat->execute([':d1' => $dateDebut . ' 00:00:00', ':d2' => $dateFin . ' 23:59:59']);
$ventesParCat = $stmtCat->fetchAll();

// ── Rapport 2 : Commandes en attente de livraison ──
$stmtAttente = $pdo->query("
    SELECT c.id, c.date_cmd, c.total, c.statut,
           cl.nom AS client_nom, cl.email, cl.telephone,
           e.statut AS statut_exp, e.adresse_livraison
    FROM commandes c
    JOIN clients cl ON cl.id = c.client_id
    LEFT JOIN expeditions e ON e.commande_id = c.id
    WHERE c.statut IN ('validee','en_attente')
    ORDER BY c.date_cmd ASC
");
$cmdAttente = $stmtAttente->fetchAll();

// ── Stats globales ──
$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM commandes WHERE statut != 'annulee') AS total_commandes,
        (SELECT COALESCE(SUM(total),0) FROM commandes WHERE statut != 'annulee') AS ca_total,
        (SELECT COUNT(*) FROM clients) AS total_clients,
        (SELECT COUNT(*) FROM produits WHERE stock = 0) AS produits_rupture
")->fetch();

// ── Ventes par mois (12 derniers mois) ──
$stmtMois = $pdo->query("
    SELECT DATE_FORMAT(date_cmd,'%Y-%m') AS mois,
           COUNT(*) AS nb_cmd,
           SUM(total) AS ca
    FROM commandes
    WHERE statut != 'annulee'
      AND date_cmd >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY mois
    ORDER BY mois ASC
");
$ventesParMois = $stmtMois->fetchAll();

// ── Top 5 produits ──
$topProduits = $pdo->query("
    SELECT p.nom, SUM(cp.quantite) AS total_vendu,
           SUM(cp.quantite*cp.prix_unitaire) AS ca
    FROM commandes_produits cp
    JOIN produits p ON p.id=cp.produit_id
    JOIN commandes c ON c.id=cp.commande_id
    WHERE c.statut != 'annulee'
    GROUP BY p.id
    ORDER BY total_vendu DESC
    LIMIT 5
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="container"><h1>Rapports & Statistiques</h1></div>
</div>
<section class="section" style="padding-top:1.5rem;">
<div class="container">
<div class="admin-layout">
    <div class="admin-sidebar">
        <h3>Administration</h3>
        <a href="produits.php"> Produits</a>
        <a href="categories.php"> Catégories</a>
        <a href="clients.php"> Clients</a>
        <a href="commandes.php"> Commandes</a>
        <a href="expeditions.php"> Expéditions</a>
        <a href="rapports.php" class="active"> Rapports</a>
        <hr style="border:none;border-top:1px solid var(--gray-200);margin:.75rem 0;">
        <a href="<?= SITE_URL ?>/index.php">Retour au site</a>
        <hr style="border:none;border-top:1px solid var(--gray-200);margin:.75rem 0;">
        <div style="padding:.5rem .75rem;background:var(--green-xlight);border-radius:var(--radius);margin-bottom:.5rem;">
            <div style="font-size:.78rem;color:var(--gray-400);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.2rem;">Connecté en tant que</div>
            <div style="font-weight:700;color:var(--green-dark);font-size:.9rem;"> <?= e($_SESSION['client_nom'] ?? '') ?></div>
            <div style="font-size:.75rem;color:var(--gray-400);">Administrateur</div>
        </div>
        <a href="<?= SITE_URL ?>/admin/logout.php" style="color:#e74c3c;font-size:.88rem;padding:.5rem .75rem;display:flex;align-items:center;gap:.4rem;">Déconnexion admin</a>
    </div>
    <div>
        <!-- KPIs -->
        <div class="stats-grid" style="margin-bottom:2rem;">
            <div class="stat-card">
                <div class="stat-value"><?= number_format($stats['total_commandes']) ?></div>
                <div class="stat-label">Commandes totales</div>
            </div>
            <div class="stat-card" style="border-color:#3498db;">
                <div class="stat-value" style="color:#2980b9;"><?= prix((float)$stats['ca_total']) ?></div>
                <div class="stat-label">Chiffre d'affaires total</div>
            </div>
            <div class="stat-card" style="border-color:#9b59b6;">
                <div class="stat-value" style="color:#8e44ad;"><?= number_format($stats['total_clients']) ?></div>
                <div class="stat-label">Clients inscrits</div>
            </div>
            <div class="stat-card" style="border-color:#e74c3c;">
                <div class="stat-value" style="color:#c0392b;"><?= number_format($stats['produits_rupture']) ?></div>
                <div class="stat-label">Produits en rupture</div>
            </div>
        </div>

        <!-- ── Rapport 1 : Ventes par catégorie ── -->
        <div class="card" style="margin-bottom:2rem;">
            <div class="card-body">
                <h2 style="font-family:var(--font-head);font-size:1.4rem;margin-bottom:1.5rem;">
                    Ventes par catégorie
                </h2>
                <!-- Filtre date -->
                <form method="GET" class="filter-bar" style="margin-bottom:1.5rem;">
                    <div class="filter-group">
                        <label>Date début</label>
                        <input type="date" name="date_debut" value="<?= e($dateDebut) ?>">
                    </div>
                    <div class="filter-group">
                        <label>Date fin</label>
                        <input type="date" name="date_fin" value="<?= e($dateFin) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Appliquer</button>
                </form>

                <?php if (empty($ventesParCat)): ?>
                    <p style="color:var(--gray-400);text-align:center;padding:1rem;">Aucune vente sur cette période.</p>
                <?php else: ?>
                    <?php $maxCA = max(array_column($ventesParCat, 'chiffre_affaires')); ?>
                    <?php foreach ($ventesParCat as $vc): ?>
                    <div style="margin-bottom:1rem;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:.3rem;font-size:.9rem;">
                            <span><strong><?= e($vc['icone']) ?> <?= e($vc['categorie']) ?></strong>
                                  — <?= $vc['total_articles'] ?> articles vendus</span>
                            <strong style="color:var(--green-dark);"><?= prix((float)$vc['chiffre_affaires']) ?></strong>
                        </div>
                        <div style="background:var(--gray-100);border-radius:50px;height:10px;overflow:hidden;">
                            <div style="background:var(--green);height:100%;width:<?= round($vc['chiffre_affaires']/$maxCA*100) ?>%;border-radius:50px;transition:width .6s;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div style="margin-top:1.5rem;">
                    <table style="width:100%;border-collapse:collapse;">
                        <thead><tr>
                            <th style="text-align:left;padding:.6rem;background:var(--green-xlight);">Catégorie</th>
                            <th style="text-align:center;padding:.6rem;background:var(--green-xlight);">Commandes</th>
                            <th style="text-align:center;padding:.6rem;background:var(--green-xlight);">Articles</th>
                            <th style="text-align:right;padding:.6rem;background:var(--green-xlight);">CA</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($ventesParCat as $vc): ?>
                        <tr style="border-bottom:1px solid var(--gray-100);">
                            <td style="padding:.6rem;"><?= e($vc['icone']) ?> <?= e($vc['categorie']) ?></td>
                            <td style="text-align:center;padding:.6rem;"><?= $vc['nb_commandes'] ?></td>
                            <td style="text-align:center;padding:.6rem;"><?= $vc['total_articles'] ?></td>
                            <td style="text-align:right;padding:.6rem;font-weight:700;color:var(--green-dark);"><?= prix((float)$vc['chiffre_affaires']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Rapport 2 : Commandes en attente ── -->
        <div class="card" style="margin-bottom:2rem;">
            <div class="card-body">
                <h2 style="font-family:var(--font-head);font-size:1.4rem;margin-bottom:1rem;">
                    Commandes en attente de livraison
                    <span class="badge badge-warning" style="font-size:.8rem;vertical-align:middle;">
                        <?= count($cmdAttente) ?>
                    </span>
                </h2>
                <?php if (empty($cmdAttente)): ?>
                    <div style="text-align:center;padding:1.5rem;color:var(--green-dark);">
                        Toutes les commandes sont traitées !
                    </div>
                <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>#</th><th>Client</th><th>Date</th><th>Total</th><th>Statut</th><th>Expédition</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($cmdAttente as $cmd): ?>
                        <tr>
                            <td>#<?= $cmd['id'] ?></td>
                            <td>
                                <strong><?= e($cmd['client_nom']) ?></strong><br>
                                <small style="color:var(--gray-400);"><?= e($cmd['email']) ?></small>
                            </td>
                            <td style="font-size:.85rem;"><?= date('d/m/Y H:i', strtotime($cmd['date_cmd'])) ?></td>
                            <td><strong style="color:var(--green-dark);"><?= prix((float)$cmd['total']) ?></strong></td>
                            <td><?= statutBadge($cmd['statut']) ?></td>
                            <td>
                                <?php if ($cmd['statut_exp']): ?>
                                    <?= statutExpBadge($cmd['statut_exp']) ?>
                                <?php else: ?>
                                    <a href="expeditions.php" class="btn btn-primary btn-sm">Créer expédition</a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="commandes.php" class="btn btn-outline btn-sm">Gérer</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top produits -->
        <div class="card">
            <div class="card-body">
                <h2 style="font-family:var(--font-head);font-size:1.4rem;margin-bottom:1rem;"> Top 5 produits vendus</h2>
                <?php foreach ($topProduits as $i => $pr): ?>
                <div style="display:flex;align-items:center;gap:1rem;padding:.75rem 0;border-bottom:1px solid var(--gray-100);">
                    <span style="font-size:1.4rem;font-weight:900;color:var(--green);min-width:2rem;"><?= $i+1 ?></span>
                    <div style="flex:1;">
                        <div style="font-weight:600;"><?= e($pr['nom']) ?></div>
                        <div style="font-size:.85rem;color:var(--gray-400);"><?= $pr['total_vendu'] ?> unités vendues</div>
                    </div>
                    <strong style="color:var(--green-dark);"><?= prix((float)$pr['ca']) ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div><!-- /.admin-content -->
</div><!-- /.admin-layout -->
</div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
