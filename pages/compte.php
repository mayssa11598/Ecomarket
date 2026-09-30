<?php
// ============================================================
//  EcoMarket – Mon Compte (profil + historique commandes)
// ============================================================
require_once __DIR__ . '/../includes/config.php';
if (!isLoggedIn()) { redirect(SITE_URL . '/pages/login.php'); }
$pageTitle = 'Mon Compte';
$pdo = getPDO();
$errors = [];

// Charger le client
$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
$stmt->execute([clientId()]);
$client = $stmt->fetch();

// Mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $nom     = trim($_POST['nom'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $tel     = trim($_POST['telephone'] ?? '');
    if ($nom === '') $errors[] = 'Le nom est requis.';

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE clients SET nom=?, adresse=?, telephone=? WHERE id=?");
        $stmt->execute([$nom, $adresse, $tel, clientId()]);
        $_SESSION['client_nom'] = $nom;
        flash('compte', 'Profil mis à jour avec succès.', 'success');
        redirect(SITE_URL . '/pages/compte.php');
    }
}

// Historique commandes
$commandes = $pdo->prepare("
    SELECT c.*, COUNT(cp.id) AS nb_articles
    FROM commandes c
    LEFT JOIN commandes_produits cp ON cp.commande_id = c.id
    WHERE c.client_id = ?
    GROUP BY c.id
    ORDER BY c.date_cmd DESC
");
$commandes->execute([clientId()]);
$historiqueCmd = $commandes->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Mon Compte</h1>
        <p>Bienvenue, <?= e($client['nom']) ?></p>
    </div>
</div>

<section class="section" style="padding-top:1.5rem;">
<div class="container">

<?= flash('compte') ?>

<div style="display:grid;grid-template-columns:1fr 1.5fr;gap:2rem;align-items:start;">

    <!-- Profil -->
    <div class="form-card" style="margin:0;">
        <h2 class="form-title">Mon Profil</h2>
        <?php foreach ($errors as $err): ?>
            <div class="alert alert-error"><?= e($err) ?></div>
        <?php endforeach; ?>
        <form method="POST" action="">
            <input type="hidden" name="action" value="update">
            <div class="form-group">
                <label class="form-label">Nom complet</label>
                <input type="text" name="nom" class="form-control"
                       value="<?= e($client['nom']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email (non modifiable)</label>
                <input type="email" class="form-control" value="<?= e($client['email']) ?>" disabled
                       style="background:var(--gray-100);color:var(--gray-400);">
            </div>
            <div class="form-group">
                <label class="form-label">Téléphone</label>
                <input type="tel" name="telephone" class="form-control"
                       value="<?= e($client['telephone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Adresse de livraison</label>
                <textarea name="adresse" class="form-control"><?= e($client['adresse'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Enregistrer</button>
        </form>
        <div style="margin-top:1rem;text-align:center;">
            <a href="logout.php" class="btn btn-danger btn-sm">Déconnexion</a>
        </div>
    </div>

    <!-- Historique commandes -->
    <div>
        <h2 style="font-family:var(--font-head);font-size:1.5rem;margin-bottom:1rem;">Mes Commandes</h2>
        <?php if (empty($historiqueCmd)): ?>
            <div class="empty-state" style="padding:2rem;">
                <div class="empty-icon"></div>
                <h3>Aucune commande</h3>
                <p>Vous n'avez pas encore passé de commande.</p>
                <a href="produits.php" class="btn btn-primary">Commencer mes achats</a>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Articles</th>
                            <th>Total</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historiqueCmd as $cmd): ?>
                        <tr>
                            <td><strong>#<?= $cmd['id'] ?></strong></td>
                            <td><?= date('d/m/Y', strtotime($cmd['date_cmd'])) ?></td>
                            <td><?= $cmd['nb_articles'] ?> article<?= $cmd['nb_articles'] > 1 ? 's' : '' ?></td>
                            <td><strong><?= prix((float)$cmd['total']) ?></strong></td>
                            <td><?= statutBadge($cmd['statut']) ?></td>
                            <td>
                                <a href="commande_detail.php?id=<?= $cmd['id'] ?>" class="btn btn-outline btn-sm">Détails</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>
</div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
