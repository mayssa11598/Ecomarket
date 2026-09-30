<?php
// ============================================================
//  EcoMarket – Admin : Gestion des Produits (CRUD + URL image)
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Admin – Produits';
$pdo = getPDO();
requireAdmin();
$errors = [];

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── AJOUTER / MODIFIER ──
if (in_array($action, ['ajouter', 'modifier']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_edit  = (int)($_POST['id'] ?? 0);
    $nom      = trim($_POST['nom'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $prix     = (float)($_POST['prix'] ?? 0);
    $stock    = (int)($_POST['stock'] ?? 0);
    $cat      = (int)($_POST['categorie_id'] ?? 0);
    $vedette  = isset($_POST['en_vedette']) ? 1 : 0;
    $oldImage = trim($_POST['old_image'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '');

    if ($nom === '') $errors[] = 'Le nom est requis.';
    if ($prix <= 0)  $errors[] = 'Le prix doit être positif.';
    if ($cat <= 0)   $errors[] = 'Veuillez sélectionner une catégorie.';

    if (empty($errors)) {
        $result = validateImageUrl($imageUrl, $oldImage);
        if (!$result['ok']) {
            $errors[] = $result['error'];
        } else {
            $image = $result['url'];
            if ($action === 'ajouter') {
                $pdo->prepare("INSERT INTO produits (nom,description,prix,stock,categorie_id,en_vedette,image) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$nom, $desc, $prix, $stock, $cat, $vedette, $image]);
                flash('admin_prod', 'Produit "' . $nom . '" ajouté avec succès. ', 'success');
            } else {
                $pdo->prepare("UPDATE produits SET nom=?,description=?,prix=?,stock=?,categorie_id=?,en_vedette=?,image=? WHERE id=?")
                    ->execute([$nom, $desc, $prix, $stock, $cat, $vedette, $image, $id_edit]);
                flash('admin_prod', 'Produit modifié avec succès. ', 'success');
            }
            redirect(SITE_URL . '/admin/produits.php');
        }
    }
}

// ── SUPPRIMER ──
if ($action === 'supprimer' && isset($_GET['id'])) {
    $id_del = (int)$_GET['id'];
    $ref = $pdo->prepare("SELECT COUNT(*) FROM commandes_produits WHERE produit_id=?");
    $ref->execute([$id_del]);
    if ((int)$ref->fetchColumn() > 0) {
        flash('admin_prod', 'Impossible de supprimer : produit présent dans des commandes.', 'error');
    } else {
        $pdo->prepare("DELETE FROM produits WHERE id=?")->execute([$id_del]);
        flash('admin_prod', 'Produit supprimé.', 'success');
    }
    redirect(SITE_URL . '/admin/produits.php');
}

// Charger produit pour édition
$edit = null;
if ($action === 'editer' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id=?");
    $stmt->execute([(int)$_GET['id']]);
    $edit = $stmt->fetch();
}

// ── Données liste ──
$categories = $pdo->query("SELECT * FROM categories ORDER BY nom")->fetchAll();
$q    = trim($_GET['q'] ?? '');
$catF = (int)($_GET['cat'] ?? 0);
$where = ['1=1']; $params = [];
if ($q !== '') { $where[] = 'p.nom LIKE :q'; $params[':q'] = "%$q%"; }
if ($catF > 0) { $where[] = 'p.categorie_id = :cat'; $params[':cat'] = $catF; }
$wSQL = implode(' AND ', $where);
$stC  = $pdo->prepare("SELECT COUNT(*) FROM produits p WHERE $wSQL");
$stC->execute($params);
$page = max(1, (int)($_GET['page'] ?? 1));
$pg   = paginate((int)$stC->fetchColumn(), $page);
$stmt = $pdo->prepare("
    SELECT p.*, c.nom AS cat_nom FROM produits p
    JOIN categories c ON c.id=p.categorie_id
    WHERE $wSQL ORDER BY p.created_at DESC
    LIMIT " . PER_PAGE . " OFFSET " . $pg['offset']);
$stmt->execute($params);
$produits = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <div class="breadcrumb"><a href="<?= SITE_URL ?>">Accueil</a> › Admin › Produits</div>
        <h1> Gestion des Produits</h1>
    </div>
</div>

<section class="section" style="padding-top:1.5rem;">
<div class="container">
<div class="admin-layout">

    <!-- Sidebar -->
    <div class="admin-sidebar">
        <h3>Administration</h3>
        <a href="produits.php"    class="active">Produits</a>
        <a href="categories.php">Catégories</a>
        <a href="clients.php">    Clients</a>
        <a href="commandes.php"> Commandes</a>
        <a href="expeditions.php"> Expéditions</a>
        <a href="rapports.php">   Rapports</a>
        <hr style="border:none;border-top:1px solid var(--gray-200);margin:.75rem 0;">
        <a href="<?= SITE_URL ?>/index.php"> Retour au site</a>
        <hr style="border:none;border-top:1px solid var(--gray-200);margin:.75rem 0;">
        <div style="padding:.5rem .75rem;background:var(--green-xlight);border-radius:var(--radius);margin-bottom:.5rem;">
            <div style="font-size:.78rem;color:var(--gray-400);font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.2rem;">Connecté en tant que</div>
            <div style="font-weight:700;color:var(--green-dark);font-size:.9rem;"><?= e($_SESSION['client_nom'] ?? '') ?></div>
            <div style="font-size:.75rem;color:var(--gray-400);">Administrateur</div>
        </div>
        <a href="<?= SITE_URL ?>/admin/logout.php" style="color:#e74c3c;font-size:.88rem;padding:.5rem .75rem;display:flex;align-items:center;gap:.4rem;"> Déconnexion admin</a>
    </div>

    <!-- Contenu principal -->
    <div>
        <?= flash('admin_prod') ?>

        <!-- ── Formulaire Ajout / Édition ── -->
        <div class="form-card" style="margin:0 0 2rem;max-width:100%;">
            <h2 class="form-title">
                <?= $edit ? ' Modifier le produit #' . $edit['id'] : 'Ajouter un produit' ?>
            </h2>

            <?php foreach ($errors as $err): ?>
                <div class="alert alert-error"><?= e($err) ?></div>
            <?php endforeach; ?>

            <form method="POST" action="">
                <input type="hidden" name="action"    value="<?= $edit ? 'modifier' : 'ajouter' ?>">
                <input type="hidden" name="old_image" value="<?= e($edit['image'] ?? '') ?>">
                <?php if ($edit): ?>
                    <input type="hidden" name="id" value="<?= $edit['id'] ?>">
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nom du produit *</label>
                        <input type="text" name="nom" class="form-control" required
                               value="<?= e($edit['nom'] ?? $_POST['nom'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Catégorie *</label>
                        <select name="categorie_id" class="form-control" required>
                            <option value="">-- Choisir --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"
                                <?= ($edit['categorie_id'] ?? $_POST['categorie_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['icone']) ?> <?= e($cat['nom']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control"><?= e($edit['description'] ?? '') ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Prix (DT) *</label>
                        <input type="number" name="prix" step="0.01" min="0" class="form-control"
                               value="<?= e((string)($edit['prix'] ?? '')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stock</label>
                        <input type="number" name="stock" min="0" class="form-control"
                               value="<?= e((string)($edit['stock'] ?? 0)) ?>">
                    </div>
                </div>

                <!-- ── Champ URL image ── -->
                <div class="form-group">
                    <label class="form-label"> URL de l'image du produit</label>

                    <?php
                    $currentImg = $edit['image'] ?? '';
                    $previewUrl = imgUrl($currentImg);
                    ?>

                    <!-- Prévisualisation de l'image actuelle -->
                    <div style="display:flex;gap:1.25rem;align-items:flex-start;margin-bottom:.85rem;">
                        <div style="flex-shrink:0;">
                            <img id="img-preview"
                                 src="<?= e($previewUrl) ?>"
                                 alt="Aperçu"
                                 style="width:100px;height:100px;object-fit:cover;
                                        border-radius:var(--radius);
                                        border:2px solid var(--green-light);
                                        background:var(--green-xlight);">
                        </div>
                        <div style="flex:1;">
                            <input type="url" name="image_url" class="form-control"
                                   value="<?= e($currentImg) ?>"
                                   placeholder="https://images.unsplash.com/photo-…?w=400&q=80">
                        </div>
                    </div>

                    <!-- Mise à jour de l'aperçu via soumission de formulaire (PHP uniquement) -->
                    <?php if (!empty($currentImg)): ?>
                    <div style="background:var(--gray-50);padding:.75rem 1rem;border-radius:var(--radius);
                                border:1px solid var(--gray-200);font-size:.83rem;color:var(--gray-600);">
                         URL actuelle :
                        <code style="word-break:break-all;color:var(--green-dark);"><?= e($currentImg) ?></code>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-group" style="display:flex;align-items:center;gap:.75rem;">
                    <input type="checkbox" name="en_vedette" id="vedette"
                           <?= ($edit['en_vedette'] ?? 0) ? 'checked' : '' ?> style="width:auto;">
                    <label for="vedette" class="form-label" style="margin:0;cursor:pointer;">
                         Afficher en vedette sur la page d'accueil
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?= $edit ? ' Enregistrer les modifications' : 'Ajouter le produit' ?>
                    </button>
                    <?php if ($edit): ?>
                        <a href="produits.php" class="btn btn-secondary"> Annuler</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- ── Filtres ── -->
        <form method="GET" action="" class="filter-bar">
            <div class="filter-group">
                <label>Recherche</label>
                <input type="text" name="q" value="<?= e($q) ?>" placeholder="Nom produit…">
            </div>
            <div class="filter-group">
                <label>Catégorie</label>
                <select name="cat">
                    <option value="">Toutes</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $catF == $cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['nom']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
            <a href="produits.php" class="btn btn-secondary btn-sm"> Réinitialiser</a>
        </form>

        <!-- ── Tableau produits ── -->
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Image</th>
                        <th>Nom</th>
                        <th>Catégorie</th>
                        <th>Prix</th>
                        <th>Stock</th>
                        <th>⭐</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($produits)): ?>
                    <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--gray-400);">Aucun produit trouvé.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($produits as $p): ?>
                    <tr>
                        <td><strong>#<?= $p['id'] ?></strong></td>
                        <td>
                            <img src="<?= e(imgUrl($p['image'] ?? '')) ?>"
                                 alt="<?= e($p['nom']) ?>"
                                 style="width:56px;height:56px;object-fit:cover;
                                        border-radius:8px;border:2px solid var(--green-light);
                                        background:var(--green-xlight);"
                                 onerror="this.src='https://placehold.co/56x56/d5f5e3/2ecc71?text=?'">
                        </td>
                        <td>
                            <strong><?= e($p['nom']) ?></strong>
                            <?php if ($p['image']): ?>
                            <br><small style="color:var(--gray-400);font-size:.75rem;word-break:break-all;">
                                <?= e(mb_strimwidth($p['image'], 0, 50, '…')) ?>
                            </small>
                            <?php endif; ?>
                        </td>
                        <td><?= e($p['cat_nom']) ?></td>
                        <td><strong style="color:var(--green-dark);"><?= prix((float)$p['prix']) ?></strong></td>
                        <td>
                            <?php if ($p['stock'] == 0): ?>
                                <span style="color:#e74c3c;font-weight:700;">0 ⚠️</span>
                            <?php else: ?>
                                <span style="font-weight:600;"><?= (int)$p['stock'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center;"><?= $p['en_vedette'] ? '⭐' : '—' ?></td>
                        <td>
                            <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
                                <a href="?action=editer&id=<?= $p['id'] ?>" class="btn btn-outline btn-sm"> Modifier</a>
                                <a href="<?= SITE_URL ?>/pages/produit_detail.php?id=<?= $p['id'] ?>"
                                   target="_blank" class="btn btn-secondary btn-sm">👁</a>
                                <a href="?action=supprimer&id=<?= $p['id'] ?>" class="btn btn-danger btn-sm"
                                   onclick="return confirm('Supprimer « <?= e(addslashes($p['nom'])) ?> » ?')">🗑</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pg['pages'] > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $pg['pages']; $i++): ?>
                <?php if ($i == $pg['page']): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?><?= $q ? '&q='.urlencode($q) : '' ?><?= $catF ? '&cat='.$catF : '' ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

    </div><!-- /.admin-content -->
</div><!-- /.admin-layout -->
</div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
