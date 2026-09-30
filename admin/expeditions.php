<?php
// ============================================================
//  EcoMarket – Admin : Gestion des Expéditions
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Admin – Expéditions';
$pdo = getPDO();
requireAdmin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Ajouter / modifier expédition
if (in_array($action,['ajouter','modifier']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_edit     = (int)($_POST['id'] ?? 0);
    $cmd_id      = (int)($_POST['commande_id'] ?? 0);
    $date_exp    = $_POST['date_expedition'] ?? '';
    $adresse     = trim($_POST['adresse_livraison'] ?? '');
    $statut      = $_POST['statut'] ?? 'preparation';
    $transporteur= trim($_POST['transporteur'] ?? '');
    $num_suivi   = trim($_POST['numero_suivi'] ?? '');
    $errors = [];
    if (!$cmd_id)        $errors[] = 'Commande requise.';
    if ($adresse === '') $errors[] = 'Adresse requise.';
    if (empty($errors)) {
        if ($action === 'ajouter') {
            $pdo->prepare("INSERT INTO expeditions (commande_id,date_expedition,adresse_livraison,statut,transporteur,numero_suivi) VALUES (?,?,?,?,?,?)")
                ->execute([$cmd_id, $date_exp ?: null, $adresse, $statut, $transporteur, $num_suivi]);
            // Mettre la commande en "expediee"
            $pdo->prepare("UPDATE commandes SET statut='expediee' WHERE id=?")->execute([$cmd_id]);
            flash('admin_exp', 'Expédition créée.', 'success');
        } else {
            $pdo->prepare("UPDATE expeditions SET commande_id=?,date_expedition=?,adresse_livraison=?,statut=?,transporteur=?,numero_suivi=? WHERE id=?")
                ->execute([$cmd_id, $date_exp ?: null, $adresse, $statut, $transporteur, $num_suivi, $id_edit]);
            flash('admin_exp', 'Expédition modifiée.', 'success');
        }
        redirect(SITE_URL . '/admin/expeditions.php');
    }
}

if ($action === 'supprimer' && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM expeditions WHERE id=?")->execute([(int)$_GET['id']]);
    flash('admin_exp', 'Expédition supprimée.', 'success');
    redirect(SITE_URL . '/admin/expeditions.php');
}

$edit = null;
if ($action === 'editer' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM expeditions WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $edit = $stmt->fetch();
}

// Commandes expédiables (validées sans expédition, ou toutes pour modifier)
$cmdSansExp = $pdo->query("
    SELECT c.id, c.date_cmd, cl.nom AS client_nom, c.total
    FROM commandes c
    JOIN clients cl ON cl.id=c.client_id
    WHERE c.statut IN ('validee','expediee')
    ORDER BY c.date_cmd DESC
")->fetchAll();

// Liste expéditions
$statutF = $_GET['statut'] ?? '';
$where = ['1=1']; $params = [];
if ($statutF !== '') { $where[] = 'e.statut=:s'; $params[':s'] = $statutF; }
$wSQL = implode(' AND ', $where);
$stmt = $pdo->prepare("
    SELECT e.*, c.date_cmd, c.total, cl.nom AS client_nom
    FROM expeditions e
    JOIN commandes c ON c.id=e.commande_id
    JOIN clients cl ON cl.id=c.client_id
    WHERE $wSQL
    ORDER BY e.id DESC
");
$stmt->execute($params);
$expeditions = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="container"><h1> Gestion des Expéditions</h1></div>
</div>
<section class="section" style="padding-top:1.5rem;">
<div class="container">
<div class="admin-layout">
    <div class="admin-sidebar">
        <h3>Administration</h3>
        <a href="produits.php"> Produits</a>
        <a href="categories.php">Catégories</a>
        <a href="clients.php"> Clients</a>
        <a href="commandes.php"> Commandes</a>
        <a href="expeditions.php" class="active">Expéditions</a>
        <a href="rapports.php">Rapports</a>
        <hr style="border:none;border-top:1px solid var(--gray-200);margin:.75rem 0;">
        <a href="<?= SITE_URL ?>/index.php"> Retour au site</a>
        <hr style="border:none;border-top:1px solid var(--gray-200);margin:.75rem 0;">
        <div style="padding:.5rem .75rem;background:var(--green-xlight);border-radius:var(--radius);margin-bottom:.5rem;">
            <div style="font-size:.78rem;color:var(--gray-400);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.2rem;">Connecté en tant que</div>
            <div style="font-weight:700;color:var(--green-dark);font-size:.9rem;">👤 <?= e($_SESSION['client_nom'] ?? '') ?></div>
            <div style="font-size:.75rem;color:var(--gray-400);">Administrateur</div>
        </div>
        <a href="<?= SITE_URL ?>/admin/logout.php" style="color:#e74c3c;font-size:.88rem;padding:.5rem .75rem;display:flex;align-items:center;gap:.4rem;">🚪 Déconnexion admin</a>
    </div>
    <div>
        <?= flash('admin_exp') ?>

        <div class="form-card" style="margin:0 0 2rem;">
            <h2 class="form-title"><?= $edit ? ' Modifier l\'expédition' : 'Créer une expédition' ?></h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?= $edit ? 'modifier' : 'ajouter' ?>">
                <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Commande *</label>
                        <select name="commande_id" class="form-control" required>
                            <option value="">-- Choisir --</option>
                            <?php foreach ($cmdSansExp as $cmd): ?>
                            <option value="<?= $cmd['id'] ?>"
                                <?= ($edit['commande_id'] ?? 0) == $cmd['id'] ? 'selected' : '' ?>>
                                #<?= $cmd['id'] ?> — <?= e($cmd['client_nom']) ?> — <?= prix((float)$cmd['total']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date d'expédition</label>
                        <input type="date" name="date_expedition" class="form-control"
                               value="<?= e($edit['date_expedition'] ?? date('Y-m-d')) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Adresse de livraison *</label>
                    <textarea name="adresse_livraison" class="form-control" required><?= e($edit['adresse_livraison'] ?? '') ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Transporteur</label>
                        <input type="text" name="transporteur" class="form-control"
                               value="<?= e($edit['transporteur'] ?? '') ?>" placeholder="Ex: Aramex, DHL…">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Numéro de suivi</label>
                        <input type="text" name="numero_suivi" class="form-control"
                               value="<?= e($edit['numero_suivi'] ?? '') ?>" placeholder="Ex: ARX-TN-…">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-control">
                        <option value="preparation" <?= ($edit['statut'] ?? '') === 'preparation' ? 'selected' : '' ?>> Préparation</option>
                        <option value="en_transit"  <?= ($edit['statut'] ?? '') === 'en_transit'  ? 'selected' : '' ?>> En transit</option>
                        <option value="livre"       <?= ($edit['statut'] ?? '') === 'livre'       ? 'selected' : '' ?>>Livré</option>
                        <option value="echec"       <?= ($edit['statut'] ?? '') === 'echec'       ? 'selected' : '' ?>> Échec</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?= $edit ? ' Enregistrer' : ' Créer' ?></button>
                    <?php if ($edit): ?><a href="expeditions.php" class="btn btn-secondary">✕ Annuler</a><?php endif; ?>
                </div>
            </form>
        </div>

        <form method="GET" class="filter-bar">
            <div class="filter-group">
                <label>Statut expédition</label>
                <select name="statut">
                    <option value="">Tous</option>
                    <option value="preparation" <?= $statutF==='preparation'?'selected':'' ?>>Préparation</option>
                    <option value="en_transit"  <?= $statutF==='en_transit' ?'selected':'' ?>>En transit</option>
                    <option value="livre"       <?= $statutF==='livre'      ?'selected':'' ?>>Livré</option>
                    <option value="echec"       <?= $statutF==='echec'      ?'selected':'' ?>>Échec</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
            <a href="expeditions.php" class="btn btn-secondary btn-sm">Tout</a>
        </form>

        <div class="table-wrapper">
            <table>
                <thead><tr><th>#</th><th>Commande</th><th>Client</th><th>Date exp.</th><th>Transporteur</th><th>N° Suivi</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (empty($expeditions)): ?>
                <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--gray-400);">Aucune expédition.</td></tr>
                <?php endif; ?>
                <?php foreach ($expeditions as $exp): ?>
                <tr>
                    <td>#<?= $exp['id'] ?></td>
                    <td><a href="<?= SITE_URL ?>/pages/commande_detail.php?id=<?= $exp['commande_id'] ?>" style="color:var(--green-dark);">#<?= $exp['commande_id'] ?></a></td>
                    <td><?= e($exp['client_nom']) ?></td>
                    <td><?= $exp['date_expedition'] ? date('d/m/Y', strtotime($exp['date_expedition'])) : '—' ?></td>
                    <td><?= e($exp['transporteur'] ?: '—') ?></td>
                    <td><code style="font-size:.8rem;"><?= e($exp['numero_suivi'] ?: '—') ?></code></td>
                    <td><?= statutExpBadge($exp['statut']) ?></td>
                    <td style="display:flex;gap:.4rem;">
                        <a href="?action=editer&id=<?= $exp['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                        <a href="?action=supprimer&id=<?= $exp['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer ?')">🗑</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
