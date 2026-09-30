<?php
// ============================================================
//  EcoMarket – Mes Commandes
// ============================================================
require_once __DIR__ . '/../includes/config.php';
if (!isLoggedIn()) { redirect(SITE_URL . '/pages/login.php'); }
$pageTitle = 'Mes Commandes';
$pdo = getPDO();

// Annulation d'une commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'annuler') {
    $id_cmd = (int)($_POST['commande_id'] ?? 0);
    if ($id_cmd) {
        // Vérifier que la commande appartient au client et est annulable
        $check = $pdo->prepare("SELECT id, statut FROM commandes WHERE id = ? AND client_id = ?");
        $check->execute([$id_cmd, clientId()]);
        $toCancel = $check->fetch();
        if ($toCancel && in_array($toCancel['statut'], ['en_attente', 'validee'])) {
            $pdo->prepare("UPDATE commandes SET statut = 'annulee' WHERE id = ?")->execute([$id_cmd]);
            flash('commandes', 'Commande #' . $id_cmd . ' annulée avec succès.', 'success');
        } else {
            flash('commandes', 'Impossible d\'annuler cette commande.', 'error');
        }
    }
    redirect(SITE_URL . '/pages/commandes.php');
}

$stmt = $pdo->prepare("
    SELECT c.*, COUNT(cp.id) AS nb_articles,
           e.statut AS statut_exp, e.numero_suivi
    FROM commandes c
    LEFT JOIN commandes_produits cp ON cp.commande_id = c.id
    LEFT JOIN expeditions e ON e.commande_id = c.id
    WHERE c.client_id = ?
    GROUP BY c.id
    ORDER BY c.date_cmd DESC
");
$stmt->execute([clientId()]);
$commandes = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="<?= SITE_URL ?>">Accueil</a> › Mes Commandes</div>
        <h1>Mes Commandes</h1>
        <p><?= count($commandes) ?> commande<?= count($commandes) > 1 ? 's' : '' ?></p>
    </div>
</div>

<section class="section" style="padding-top:1.5rem;">
<div class="container">
<?= flash('commandes') ?>

<?php if (empty($commandes)): ?>
    <div class="empty-state">
        <div class="empty-icon">📭</div>
        <h3>Aucune commande</h3>
        <p>Vous n'avez pas encore passé de commande.</p>
        <a href="produits.php" class="btn btn-primary">Commencer mes achats</a>
    </div>
<?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>N° Commande</th>
                    <th>Date</th>
                    <th>Articles</th>
                    <th>Total</th>
                    <th>Statut Commande</th>
                    <th>Expédition</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($commandes as $cmd): ?>
                <tr>
                    <td><strong>#<?= $cmd['id'] ?></strong></td>
                    <td><?= date('d/m/Y à H:i', strtotime($cmd['date_cmd'])) ?></td>
                    <td><?= $cmd['nb_articles'] ?> article<?= $cmd['nb_articles'] > 1 ? 's' : '' ?></td>
                    <td><strong style="color:var(--green-dark);"><?= prix((float)$cmd['total']) ?></strong></td>
                    <td><?= statutBadge($cmd['statut']) ?></td>
                    <td>
                        <?php if ($cmd['statut_exp']): ?>
                            <?= statutExpBadge($cmd['statut_exp']) ?>
                            <?php if ($cmd['numero_suivi']): ?>
                                <br><small style="color:var(--gray-400);"><?= e($cmd['numero_suivi']) ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color:var(--gray-400);font-size:.85rem;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
                        <a href="commande_detail.php?id=<?= $cmd['id'] ?>" class="btn btn-outline btn-sm">Détails</a>
                        <?php if (in_array($cmd['statut'], ['en_attente', 'validee'])): ?>
                        <form method="POST" action="" style="display:inline;"
                              onsubmit="return confirm('Confirmer l\'annulation de la commande #<?= $cmd['id'] ?> ?');">
                            <input type="hidden" name="action" value="annuler">
                            <input type="hidden" name="commande_id" value="<?= $cmd['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Annuler</button>
                        </form>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
</div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
