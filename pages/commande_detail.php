<?php
// ============================================================
//  EcoMarket – Détail d'une commande
// ============================================================
require_once __DIR__ . '/../includes/config.php';
if (!isLoggedIn()) { redirect(SITE_URL . '/pages/login.php'); }
$pageTitle = 'Détail Commande';
$pdo = getPDO();

// Annulation depuis la page détail
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'annuler') {
    $id_post = (int)($_POST['commande_id'] ?? 0);
    if ($id_post) {
        $check = $pdo->prepare("SELECT id, statut FROM commandes WHERE id = ? AND client_id = ?");
        $check->execute([$id_post, clientId()]);
        $toCancel = $check->fetch();
        if ($toCancel && in_array($toCancel['statut'], ['en_attente', 'validee'])) {
            $pdo->prepare("UPDATE commandes SET statut = 'annulee' WHERE id = ?")->execute([$id_post]);
            flash('detail_cmd', 'Commande annulée avec succès.', 'success');
        } else {
            flash('detail_cmd', 'Impossible d\'annuler cette commande.', 'error');
        }
    }
    redirect(SITE_URL . '/pages/commande_detail.php?id=' . $id_post);
}

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT c.*, cl.nom AS client_nom, cl.email FROM commandes c JOIN clients cl ON cl.id=c.client_id WHERE c.id=? AND c.client_id=?");
$stmt->execute([$id, clientId()]);
$cmd = $stmt->fetch();
if (!$cmd) { redirect(SITE_URL . '/pages/commandes.php'); }

// Produits de la commande
$articles = $pdo->prepare("SELECT cp.*, p.nom AS prod_nom FROM commandes_produits cp JOIN produits p ON p.id=cp.produit_id WHERE cp.commande_id=?");
$articles->execute([$id]);
$articles = $articles->fetchAll();

// Expédition
$exp = $pdo->prepare("SELECT * FROM expeditions WHERE commande_id=?");
$exp->execute([$id]);
$exp = $exp->fetch();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?= SITE_URL ?>">Accueil</a> › <a href="commandes.php">Commandes</a> › #<?= $id ?>
        </div>
        <h1>Commande #<?= $id ?></h1>
        <p>Passée le <?= date('d/m/Y à H:i', strtotime($cmd['date_cmd'])) ?> — <?= statutBadge($cmd['statut']) ?></p>
    </div>
</div>
<section class="section" style="padding-top:1.5rem;">
<div class="container">
<div style="display:grid;grid-template-columns:2fr 1fr;gap:2rem;align-items:start;">
    <div>
        <div class="card">
            <div class="card-body">
                <h3 style="font-family:var(--font-head);margin-bottom:1rem;">Articles commandés</h3>
                <table style="width:100%;border-collapse:collapse;">
                    <thead><tr>
                        <th style="text-align:left;padding:.5rem;background:var(--green-xlight);">Produit</th>
                        <th style="text-align:center;padding:.5rem;background:var(--green-xlight);">Qté</th>
                        <th style="text-align:right;padding:.5rem;background:var(--green-xlight);">Prix unit.</th>
                        <th style="text-align:right;padding:.5rem;background:var(--green-xlight);">Sous-total</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($articles as $a): ?>
                    <tr style="border-bottom:1px solid var(--gray-100);">
                        <td style="padding:.6rem .5rem;"><?= e($a['prod_nom']) ?></td>
                        <td style="text-align:center;padding:.6rem;"><?= $a['quantite'] ?></td>
                        <td style="text-align:right;padding:.6rem;"><?= prix((float)$a['prix_unitaire']) ?></td>
                        <td style="text-align:right;padding:.6rem;font-weight:700;color:var(--green-dark);"><?= prix((float)$a['prix_unitaire'] * $a['quantite']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr style="border-top:2px solid var(--green);">
                        <td colspan="3" style="padding:.75rem .5rem;text-align:right;font-weight:700;">Total</td>
                        <td style="padding:.75rem .5rem;text-align:right;font-weight:700;font-size:1.1rem;color:var(--green-dark);"><?= prix((float)$cmd['total']) ?></td>
                    </tr></tfoot>
                </table>
            </div>
        </div>
        <?php if ($cmd['notes']): ?>
        <div class="card" style="margin-top:1rem;">
            <div class="card-body"><strong>Notes :</strong> <?= e($cmd['notes']) ?></div>
        </div>
        <?php endif; ?>
    </div>
    <div>
        <?php if ($exp): ?>
        <div class="card" style="margin-bottom:1rem;">
            <div class="card-body">
                <h4 style="font-family:var(--font-head);margin-bottom:.75rem;">🚚 Expédition</h4>
                <div style="font-size:.9rem;line-height:1.8;">
                    <div><strong>Statut :</strong> <?= statutExpBadge($exp['statut']) ?></div>
                    <?php if ($exp['date_expedition']): ?>
                    <div><strong>Date :</strong> <?= date('d/m/Y', strtotime($exp['date_expedition'])) ?></div>
                    <?php endif; ?>
                    <?php if ($exp['transporteur']): ?>
                    <div><strong>Transporteur :</strong> <?= e($exp['transporteur']) ?></div>
                    <?php endif; ?>
                    <?php if ($exp['numero_suivi']): ?>
                    <div><strong>N° suivi :</strong> <?= e($exp['numero_suivi']) ?></div>
                    <?php endif; ?>
                    <div style="margin-top:.5rem;"><strong>Adresse :</strong><br><?= nl2br(e($exp['adresse_livraison'])) ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="card">
            <div class="card-body">
                <h4 style="font-family:var(--font-head);margin-bottom:.75rem;">👤 Client</h4>
                <div style="font-size:.9rem;line-height:1.8;">
                    <div><strong><?= e($cmd['client_nom']) ?></strong></div>
                    <div><?= e($cmd['email']) ?></div>
                </div>
            </div>
        </div>
        <div style="margin-top:1rem;">
            <?= flash('detail_cmd') ?>
            <?php if (in_array($cmd['statut'], ['en_attente', 'validee'])): ?>
            <form method="POST" action="" style="margin-bottom:.75rem;"
                  onsubmit="return confirm('Confirmer l\'annulation de cette commande ?');">
                <input type="hidden" name="action" value="annuler">
                <input type="hidden" name="commande_id" value="<?= $cmd['id'] ?>">
                <button type="submit" class="btn btn-danger" style="width:100%;">❌ Annuler la commande</button>
            </form>
            <?php endif; ?>
            <a href="commandes.php" class="btn btn-outline" style="width:100%;display:block;text-align:center;">← Retour aux commandes</a>
        </div>
    </div>
</div>
</div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
