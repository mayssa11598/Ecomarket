<?php
// ============================================================
//  EcoMarket – Admin : Gestion des Commandes
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Admin – Commandes';
$pdo = getPDO();
requireAdmin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Changer le statut d'une commande
if ($action === 'changer_statut' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cmd = (int)($_POST['commande_id'] ?? 0);
    $statut = $_POST['statut'] ?? '';
    $valid  = ['en_attente','validee','expediee','livree','annulee'];
    if ($id_cmd && in_array($statut, $valid)) {
        $pdo->prepare("UPDATE commandes SET statut=? WHERE id=?")->execute([$statut, $id_cmd]);
        flash('admin_cmd', 'Statut mis à jour.', 'success');
    }
    redirect(SITE_URL . '/admin/commandes.php');
}

// Créer une commande manuellement
if ($action === 'creer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = (int)($_POST['client_id'] ?? 0);
    $produits  = $_POST['produits'] ?? [];
    $quantites = $_POST['quantites'] ?? [];
    $notes     = trim($_POST['notes'] ?? '');
    $errors = [];
    if (!$client_id) $errors[] = 'Veuillez sélectionner un client.';
    if (empty($produits)) $errors[] = 'Sélectionnez au moins un produit.';
    if (empty($errors)) {
        $total = 0;
        $lignes = [];
        foreach ($produits as $i => $pid) {
            $pid = (int)$pid;
            $qty = max(1,(int)($quantites[$i] ?? 1));
            $stmt = $pdo->prepare("SELECT prix, stock FROM produits WHERE id=?");
            $stmt->execute([$pid]);
            $row = $stmt->fetch();
            if ($row && $row['stock'] >= $qty) {
                $total += $row['prix'] * $qty;
                $lignes[] = ['pid' => $pid, 'qty' => $qty, 'prix' => $row['prix']];
            }
        }
        if (!empty($lignes)) {
            $pdo->beginTransaction();
            $pdo->prepare("INSERT INTO commandes (client_id,total,notes) VALUES (?,?,?)")->execute([$client_id,$total,$notes]);
            $cmdId = $pdo->lastInsertId();
            foreach ($lignes as $l) {
                $pdo->prepare("INSERT INTO commandes_produits (commande_id,produit_id,quantite,prix_unitaire) VALUES (?,?,?,?)")->execute([$cmdId,$l['pid'],$l['qty'],$l['prix']]);
                $pdo->prepare("UPDATE produits SET stock=stock-? WHERE id=?")->execute([$l['qty'],$l['pid']]);
            }
            $pdo->commit();
            flash('admin_cmd', 'Commande #'.$cmdId.' créée.', 'success');
            redirect(SITE_URL . '/admin/commandes.php');
        }
    }
}

// Supprimer commande
if ($action === 'supprimer' && isset($_GET['id'])) {
    $pdo->prepare("DELETE FROM commandes WHERE id=?")->execute([(int)$_GET['id']]);
    flash('admin_cmd', 'Commande supprimée.', 'success');
    redirect(SITE_URL . '/admin/commandes.php');
}

// ── Filtres et liste ──
$statutF = $_GET['statut'] ?? '';
$page = max(1,(int)($_GET['page'] ?? 1));
$where = ['1=1']; $params = [];
if ($statutF !== '') { $where[] = 'c.statut=:s'; $params[':s'] = $statutF; }
$wSQL = implode(' AND ', $where);
$stC = $pdo->prepare("SELECT COUNT(*) FROM commandes c WHERE $wSQL"); $stC->execute($params);
$pg = paginate((int)$stC->fetchColumn(), $page);

$stmt = $pdo->prepare("
    SELECT c.*, cl.nom AS client_nom, COUNT(cp.id) AS nb_art
    FROM commandes c
    JOIN clients cl ON cl.id=c.client_id
    LEFT JOIN commandes_produits cp ON cp.commande_id=c.id
    WHERE $wSQL
    GROUP BY c.id
    ORDER BY c.date_cmd DESC
    LIMIT ".PER_PAGE." OFFSET ".$pg['offset']);
$stmt->execute($params);
$commandes = $stmt->fetchAll();

$clients  = $pdo->query("SELECT id, nom FROM clients ORDER BY nom")->fetchAll();
$produits = $pdo->query("SELECT id, nom, prix, stock FROM produits WHERE stock>0 ORDER BY nom")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="container"><h1> Gestion des Commandes</h1></div>
</div>
<section class="section" style="padding-top:1.5rem;">
<div class="container">
<div class="admin-layout">
    <div class="admin-sidebar">
        <h3>Administration</h3>
        <a href="produits.php">Produits</a>
        <a href="categories.php"> Catégories</a>
        <a href="clients.php">Clients</a>
        <a href="commandes.php" class="active"> Commandes</a>
        <a href="expeditions.php"> Expéditions</a>
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
        <?= flash('admin_cmd') ?>

        <!-- Créer commande manuelle -->
        <details class="form-card" style="margin:0 0 2rem;">
            <summary style="cursor:pointer;font-family:var(--font-head);font-size:1.1rem;font-weight:700;color:var(--green-dark);">
                 Créer une commande manuelle
            </summary>
            <div style="margin-top:1.5rem;">
            <form method="POST" action="">
                <input type="hidden" name="action" value="creer">
                <div class="form-group">
                    <label class="form-label">Client *</label>
                    <select name="client_id" class="form-control" required>
                        <option value="">-- Sélectionner --</option>
                        <?php foreach ($clients as $cl): ?>
                        <option value="<?= $cl['id'] ?>"><?= e($cl['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="margin-bottom:1rem;">
                    <label class="form-label">Produits *</label>
                    <?php foreach ($produits as $pr): ?>
                    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem;background:var(--gray-50);padding:.5rem;border-radius:var(--radius);">
                        <input type="checkbox" name="produits[]" value="<?= $pr['id'] ?>"
                               id="prod_<?= $pr['id'] ?>" style="width:auto;">
                        <label for="prod_<?= $pr['id'] ?>" style="flex:1;font-size:.9rem;">
                            <?= e($pr['nom']) ?> — <?= prix((float)$pr['prix']) ?>
                            <span style="color:var(--gray-400);font-size:.8rem;">(stock: <?= $pr['stock'] ?>)</span>
                        </label>
                        <input type="number" name="quantites[]" value="1" min="1" max="<?= $pr['stock'] ?>"
                               style="width:60px;padding:.3rem;border:1.5px solid var(--gray-200);border-radius:6px;">
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-primary"> Créer la commande</button>
            </form>
            </div>
        </details>

        <!-- Filtre statut -->
        <form method="GET" class="filter-bar">
            <div class="filter-group">
                <label>Filtrer par statut</label>
                <select name="statut">
                    <option value="">Tous les statuts</option>
                    <option value="en_attente" <?= $statutF==='en_attente' ? 'selected':'' ?>>En attente</option>
                    <option value="validee"    <?= $statutF==='validee'    ? 'selected':'' ?>>Validée</option>
                    <option value="expediee"   <?= $statutF==='expediee'   ? 'selected':'' ?>>Expédiée</option>
                    <option value="livree"     <?= $statutF==='livree'     ? 'selected':'' ?>>Livrée</option>
                    <option value="annulee"    <?= $statutF==='annulee'    ? 'selected':'' ?>>Annulée</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
            <a href="commandes.php" class="btn btn-secondary btn-sm">Tout voir</a>
        </form>

        <div class="table-wrapper">
            <table>
                <thead><tr><th>#</th><th>Client</th><th>Date</th><th>Articles</th><th>Total</th><th>Statut</th><th>Changer statut</th><th>Action</th></tr></thead>
                <tbody>
                <?php if (empty($commandes)): ?>
                <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--gray-400);">Aucune commande.</td></tr>
                <?php endif; ?>
                <?php foreach ($commandes as $cmd): ?>
                <tr>
                    <td><strong>#<?= $cmd['id'] ?></strong></td>
                    <td><?= e($cmd['client_nom']) ?></td>
                    <td style="font-size:.85rem;"><?= date('d/m/Y H:i', strtotime($cmd['date_cmd'])) ?></td>
                    <td><?= $cmd['nb_art'] ?></td>
                    <td><strong style="color:var(--green-dark);"><?= prix((float)$cmd['total']) ?></strong></td>
                    <td><?= statutBadge($cmd['statut']) ?></td>
                    <td>
                        <form method="POST" action="" style="display:flex;gap:.3rem;align-items:center;">
                            <input type="hidden" name="action"      value="changer_statut">
                            <input type="hidden" name="commande_id" value="<?= $cmd['id'] ?>">
                            <select name="statut" style="padding:.3rem .5rem;border:1.5px solid var(--gray-200);border-radius:6px;font-size:.82rem;">
                                <option value="en_attente" <?= $cmd['statut']==='en_attente'?'selected':'' ?>>En attente</option>
                                <option value="validee"    <?= $cmd['statut']==='validee'   ?'selected':'' ?>>Validée</option>
                                <option value="expediee"   <?= $cmd['statut']==='expediee'  ?'selected':'' ?>>Expédiée</option>
                                <option value="livree"     <?= $cmd['statut']==='livree'    ?'selected':'' ?>>Livrée</option>
                                <option value="annulee"    <?= $cmd['statut']==='annulee'   ?'selected':'' ?>>Annulée</option>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">✓</button>
                        </form>
                    </td>
                    <td>
                        <a href="<?= SITE_URL ?>/pages/commande_detail.php?id=<?= $cmd['id'] ?>" class="btn btn-outline btn-sm">👁</a>
                        <a href="?action=supprimer&id=<?= $cmd['id'] ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('Supprimer ?')">🗑</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pg['pages'] > 1): ?>
        <div class="pagination">
            <?php for ($i=1;$i<=$pg['pages'];$i++): ?>
                <?php if ($i==$pg['page']): ?><span class="current"><?=$i?></span>
                <?php else: ?><a href="?page=<?=$i?><?=$statutF?'&statut='.urlencode($statutF):''?>"><?=$i?></a><?php endif; ?>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
