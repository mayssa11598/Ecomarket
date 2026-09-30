<?php
// ============================================================
//  EcoMarket – Admin : Gestion des Clients
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Admin – Clients';
$pdo = getPDO();
requireAdmin();
$errors = [];

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (in_array($action, ['ajouter','modifier']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_edit = (int)($_POST['id'] ?? 0);
    $nom     = trim($_POST['nom'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $tel     = trim($_POST['telephone'] ?? '');
    $mdp     = $_POST['mot_de_passe'] ?? '';

    if ($nom === '') $errors[] = 'Nom requis.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';

    if (empty($errors)) {
        if ($action === 'ajouter') {
            if (strlen($mdp) < 6) { $errors[] = 'Mot de passe : 6 caractères minimum.'; }
            else {
                $hash = password_hash($mdp, PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO clients (nom,email,mot_de_passe,adresse,telephone) VALUES (?,?,?,?,?)")->execute([$nom,$email,$hash,$adresse,$tel]);
                flash('admin_cli', 'Client ajouté.', 'success');
                redirect(SITE_URL . '/admin/clients.php');
            }
        } else {
            if ($mdp !== '') {
                $hash = password_hash($mdp, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE clients SET nom=?,email=?,mot_de_passe=?,adresse=?,telephone=? WHERE id=?")->execute([$nom,$email,$hash,$adresse,$tel,$id_edit]);
            } else {
                $pdo->prepare("UPDATE clients SET nom=?,email=?,adresse=?,telephone=? WHERE id=?")->execute([$nom,$email,$adresse,$tel,$id_edit]);
            }
            flash('admin_cli', 'Client modifié.', 'success');
            redirect(SITE_URL . '/admin/clients.php');
        }
    }
}

if ($action === 'supprimer' && isset($_GET['id'])) {
    $ref = $pdo->prepare("SELECT COUNT(*) FROM commandes WHERE client_id=?");
    $ref->execute([(int)$_GET['id']]);
    if ((int)$ref->fetchColumn() > 0) {
        flash('admin_cli', 'Impossible : ce client a des commandes.', 'error');
    } else {
        $pdo->prepare("DELETE FROM clients WHERE id=?")->execute([(int)$_GET['id']]);
        flash('admin_cli', 'Client supprimé.', 'success');
    }
    redirect(SITE_URL . '/admin/clients.php');
}

$edit = null;
if ($action === 'editer' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $edit = $stmt->fetch();
}

$q = trim($_GET['q'] ?? '');
$page = max(1,(int)($_GET['page'] ?? 1));
$where = ['1=1']; $params = [];
if ($q !== '') { $where[] = '(nom LIKE :q OR email LIKE :q2)'; $params[':q'] = "%$q%"; $params[':q2'] = "%$q%"; }
$wSQL = implode(' AND ', $where);
$stC = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE $wSQL"); $stC->execute($params);
$pg = paginate((int)$stC->fetchColumn(), $page);
$stmt = $pdo->prepare("SELECT c.*, COUNT(cmd.id) AS nb_cmd FROM clients c LEFT JOIN commandes cmd ON cmd.client_id=c.id WHERE $wSQL GROUP BY c.id ORDER BY c.created_at DESC LIMIT ".PER_PAGE." OFFSET ".$pg['offset']);
$stmt->execute($params);
$clients = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="container"><h1> Gestion des Clients</h1></div>
</div>
<section class="section" style="padding-top:1.5rem;">
<div class="container">
<div class="admin-layout">
    <div class="admin-sidebar">
        <h3>Administration</h3>
        <a href="produits.php">Produits</a>
        <a href="categories.php">Catégories</a>
        <a href="clients.php" class="active"> Clients</a>
        <a href="commandes.php"> Commandes</a>
        <a href="expeditions.php"> Expéditions</a>
        <a href="rapports.php"> Rapports</a>
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
        <?= flash('admin_cli') ?>

        <div class="form-card" style="margin:0 0 2rem;">
            <h2 class="form-title"><?= $edit ? '✏️ Modifier le client' : '➕ Ajouter un client' ?></h2>
            <?php foreach ($errors as $err): ?><div class="alert alert-error">❌ <?= e($err) ?></div><?php endforeach; ?>
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?= $edit ? 'modifier' : 'ajouter' ?>">
                <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nom complet *</label>
                        <input type="text" name="nom" class="form-control" required value="<?= e($edit['nom'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" name="telephone" class="form-control" value="<?= e($edit['telephone'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" required value="<?= e($edit['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Mot de passe <?= $edit ? '(laisser vide = inchangé)' : '*' ?></label>
                    <input type="password" name="mot_de_passe" class="form-control" <?= !$edit ? 'required' : '' ?> placeholder="Min. 6 caractères">
                </div>
                <div class="form-group">
                    <label class="form-label">Adresse</label>
                    <textarea name="adresse" class="form-control"><?= e($edit['adresse'] ?? '') ?></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?= $edit ? '💾 Enregistrer' : '➕ Ajouter' ?></button>
                    <?php if ($edit): ?><a href="clients.php" class="btn btn-secondary">✕ Annuler</a><?php endif; ?>
                </div>
            </form>
        </div>

        <form method="GET" class="filter-bar">
            <div class="filter-group">
                <label>Recherche</label>
                <input type="text" name="q" value="<?= e($q) ?>" placeholder="Nom ou email…">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
            <a href="clients.php" class="btn btn-secondary btn-sm">Réinitialiser</a>
        </form>

        <div class="table-wrapper">
            <table>
                <thead><tr><th>ID</th><th>Nom</th><th>Email</th><th>Téléphone</th><th>Commandes</th><th>Inscrit le</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($clients as $cl): ?>
                <tr>
                    <td>#<?= $cl['id'] ?></td>
                    <td><strong><?= e($cl['nom']) ?></strong></td>
                    <td><?= e($cl['email']) ?></td>
                    <td><?= e($cl['telephone'] ?? '—') ?></td>
                    <td><span class="badge badge-info"><?= $cl['nb_cmd'] ?></span></td>
                    <td style="font-size:.85rem;"><?= date('d/m/Y', strtotime($cl['created_at'])) ?></td>
                    <td style="display:flex;gap:.4rem;">
                        <a href="?action=editer&id=<?= $cl['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                        <a href="?action=supprimer&id=<?= $cl['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer ce client ?')">🗑</a>
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
                <?php else: ?><a href="?page=<?=$i?><?=$q?'&q='.urlencode($q):''?>"><?=$i?></a><?php endif; ?>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
