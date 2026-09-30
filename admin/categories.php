<?php
// ============================================================
//  EcoMarket – Admin : Gestion des Catégories
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Admin – Catégories';
$pdo = getPDO();
requireAdmin();
$errors = [];

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (in_array($action, ['ajouter','modifier']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_edit = (int)($_POST['id'] ?? 0);
    $nom     = trim($_POST['nom'] ?? '');
    $desc    = trim($_POST['description'] ?? '');
    $icone   = trim($_POST['icone'] ?? '🌿');
    if ($nom === '') $errors[] = 'Le nom est requis.';
    if (empty($errors)) {
        if ($action === 'ajouter') {
            $pdo->prepare("INSERT INTO categories (nom,description,icone) VALUES (?,?,?)")->execute([$nom,$desc,$icone]);
            flash('admin_cat', 'Catégorie ajoutée.', 'success');
        } else {
            $pdo->prepare("UPDATE categories SET nom=?,description=?,icone=? WHERE id=?")->execute([$nom,$desc,$icone,$id_edit]);
            flash('admin_cat', 'Catégorie modifiée.', 'success');
        }
        redirect(SITE_URL . '/admin/categories.php');
    }
}

if ($action === 'supprimer' && isset($_GET['id'])) {
    $ref = $pdo->prepare("SELECT COUNT(*) FROM produits WHERE categorie_id=?");
    $ref->execute([(int)$_GET['id']]);
    if ((int)$ref->fetchColumn() > 0) {
        flash('admin_cat', 'Impossible : des produits utilisent cette catégorie.', 'error');
    } else {
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([(int)$_GET['id']]);
        flash('admin_cat', 'Catégorie supprimée.', 'success');
    }
    redirect(SITE_URL . '/admin/categories.php');
}

$edit = null;
if ($action === 'editer' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $edit = $stmt->fetch();
}

$categories = $pdo->query("SELECT c.*, COUNT(p.id) AS nb FROM categories c LEFT JOIN produits p ON p.categorie_id=c.id GROUP BY c.id ORDER BY c.nom")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="container">
        <h1>Gestion des Catégories</h1>
    </div>
</div>
<section class="section" style="padding-top:1.5rem;">
<div class="container">
<div class="admin-layout">
    <div class="admin-sidebar">
        <h3>Administration</h3>
        <a href="produits.php"> Produits</a>
        <a href="categories.php" class="active">Catégories</a>
        <a href="clients.php"> Clients</a>
        <a href="commandes.php">Commandes</a>
        <a href="expeditions.php">Expéditions</a>
        <a href="rapports.php">Rapports</a>
        <hr style="border:none;border-top:1px solid var(--gray-200);margin:.75rem 0;">
        <a href="<?= SITE_URL ?>/index.php">Retour au site</a>
        <hr style="border:none;border-top:1px solid var(--gray-200);margin:.75rem 0;">
        <div style="padding:.5rem .75rem;background:var(--green-xlight);border-radius:var(--radius);margin-bottom:.5rem;">
            <div style="font-size:.78rem;color:var(--gray-400);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.2rem;">Connecté en tant que</div>
            <div style="font-weight:700;color:var(--green-dark);font-size:.9rem;">👤 <?= e($_SESSION['client_nom'] ?? '') ?></div>
            <div style="font-size:.75rem;color:var(--gray-400);">Administrateur</div>
        </div>
        <a href="<?= SITE_URL ?>/admin/logout.php" style="color:#e74c3c;font-size:.88rem;padding:.5rem .75rem;display:flex;align-items:center;gap:.4rem;">🚪 Déconnexion admin</a>
    </div>
    <div>
        <?= flash('admin_cat') ?>
        <div class="form-card" style="margin:0 0 2rem;">
            <h2 class="form-title"><?= $edit ? '✏️ Modifier la catégorie' : '➕ Ajouter une catégorie' ?></h2>
            <?php foreach ($errors as $err): ?>
                <div class="alert alert-error">❌ <?= e($err) ?></div>
            <?php endforeach; ?>
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?= $edit ? 'modifier' : 'ajouter' ?>">
                <?php if ($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="nom" class="form-control" required
                               value="<?= e($edit['nom'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Icône (emoji)</label>
                        <input type="text" name="icone" class="form-control"
                               value="<?= e($edit['icone'] ?? '🌿') ?>" placeholder="🌿">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control"><?= e($edit['description'] ?? '') ?></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?= $edit ? '💾 Enregistrer' : '➕ Ajouter' ?>
                    </button>
                    <?php if ($edit): ?><a href="categories.php" class="btn btn-secondary">✕ Annuler</a><?php endif; ?>
                </div>
            </form>
        </div>

        <div class="table-wrapper">
            <table>
                <thead><tr><th>ID</th><th>Icône</th><th>Nom</th><th>Description</th><th>Produits</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td>#<?= $cat['id'] ?></td>
                    <td style="font-size:1.5rem;"><?= e($cat['icone']) ?></td>
                    <td><strong><?= e($cat['nom']) ?></strong></td>
                    <td style="font-size:.88rem;color:var(--gray-600);"><?= e(mb_strimwidth($cat['description'] ?? '', 0, 60, '…')) ?></td>
                    <td><span class="badge badge-info"><?= $cat['nb'] ?></span></td>
                    <td style="display:flex;gap:.4rem;">
                        <a href="?action=editer&id=<?= $cat['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                        <a href="?action=supprimer&id=<?= $cat['id'] ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('Supprimer ?')">🗑</a>
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
