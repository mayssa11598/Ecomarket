<?php
// ============================================================
//  EcoMarket – Suivi des expéditions
// ============================================================
require_once __DIR__ . '/../includes/config.php';
if (!isLoggedIn()) { redirect(SITE_URL . '/pages/login.php'); }
$pageTitle = 'Suivi Livraison';
$pdo = getPDO();

$stmt = $pdo->prepare("
    SELECT e.*, c.date_cmd, c.total, c.statut AS statut_cmd
    FROM expeditions e
    JOIN commandes c ON c.id = e.commande_id
    WHERE c.client_id = ?
    ORDER BY c.date_cmd DESC
");
$stmt->execute([clientId()]);
$expeditions = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="<?= SITE_URL ?>">Accueil</a> › Suivi Livraison</div>
        <h1>Suivi de mes Livraisons</h1>
    </div>
</div>
<section class="section" style="padding-top:1.5rem;">
<div class="container">
<?php if (empty($expeditions)): ?>
    <div class="empty-state">
        <div class="empty-icon"></div>
        <h3>Aucune expédition</h3>
        <p>Vos expéditions apparaîtront ici après validation de vos commandes.</p>
        <a href="produits.php" class="btn btn-primary">Découvrir les produits</a>
    </div>
<?php else: ?>
    <?php foreach ($expeditions as $exp): ?>
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-body">
            <div style="display:flex;justify-content:space-between;align-items:start;flex-wrap:wrap;gap:1rem;">
                <div>
                    <h3 style="font-family:var(--font-head);">Commande #<?= $exp['commande_id'] ?></h3>
                    <p style="color:var(--gray-600);font-size:.9rem;">
                        Passée le <?= date('d/m/Y', strtotime($exp['date_cmd'])) ?> —
                        Total : <strong><?= prix((float)$exp['total']) ?></strong>
                    </p>
                </div>
                <div style="text-align:right;">
                    <?= statutExpBadge($exp['statut']) ?>
                </div>
            </div>

            <!-- Timeline d'expédition -->
            <div style="margin:1.5rem 0;display:flex;gap:0;position:relative;">
                <?php
                $steps = [
                    'preparation' => ['', 'Préparation'],
                    'en_transit'  => ['', 'En transit'],
                    'livre'       => ['', 'Livré'],
                ];
                $order = array_keys($steps);
                $currentIdx = array_search($exp['statut'], $order);
                if ($currentIdx === false) $currentIdx = -1;
                ?>
                <?php foreach ($steps as $key => [$icon, $label]): ?>
                <?php $idx = array_search($key, $order); ?>
                <div style="flex:1;text-align:center;position:relative;">
                    <?php if ($idx > 0): ?>
                    <div style="position:absolute;top:20px;left:-50%;width:100%;height:3px;background:<?= $idx <= $currentIdx ? 'var(--green)' : 'var(--gray-200)' ?>;z-index:0;"></div>
                    <?php endif; ?>
                    <div style="width:42px;height:42px;border-radius:50%;margin:0 auto .5rem;display:flex;align-items:center;justify-content:center;font-size:1.2rem;position:relative;z-index:1;
                        background:<?= $idx <= $currentIdx ? 'var(--green)' : 'var(--gray-200)' ?>;
                        color:<?= $idx <= $currentIdx ? 'white' : 'var(--gray-400)' ?>;">
                        <?= $icon ?>
                    </div>
                    <div style="font-size:.8rem;font-weight:600;color:<?= $idx <= $currentIdx ? 'var(--green-dark)' : 'var(--gray-400)' ?>;"><?= $label ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;font-size:.9rem;margin-top:1rem;">
                <?php if ($exp['date_expedition']): ?>
                <div><strong>Date d'expédition :</strong><br><?= date('d/m/Y', strtotime($exp['date_expedition'])) ?></div>
                <?php endif; ?>
                <?php if ($exp['transporteur']): ?>
                <div><strong>Transporteur :</strong><br><?= e($exp['transporteur']) ?></div>
                <?php endif; ?>
                <?php if ($exp['numero_suivi']): ?>
                <div><strong>Numéro de suivi :</strong><br><code style="background:var(--gray-100);padding:.2rem .5rem;border-radius:4px;"><?= e($exp['numero_suivi']) ?></code></div>
                <?php endif; ?>
                <div><strong>Adresse de livraison :</strong><br><?= nl2br(e($exp['adresse_livraison'])) ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
