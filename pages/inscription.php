<?php
// ============================================================
//  EcoMarket – Inscription client
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Inscription';
$pdo = getPDO();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom      = trim($_POST['nom'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $mdp      = $_POST['mot_de_passe'] ?? '';
    $mdp2     = $_POST['mdp_confirm'] ?? '';
    $adresse  = trim($_POST['adresse'] ?? '');
    $tel      = trim($_POST['telephone'] ?? '');

    if ($nom === '')   $errors[] = 'Nom requis.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email invalide.';
    if (strlen($mdp) < 6) $errors[] = 'Mot de passe : 6 caractères minimum.';
    if ($mdp !== $mdp2)   $errors[] = 'Les mots de passe ne correspondent pas.';

    if (empty($errors)) {
        // Vérifier unicité email
        $stmt = $pdo->prepare("SELECT id FROM clients WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Cet email est déjà utilisé.';
        } else {
            $hash = password_hash($mdp, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO clients (nom, email, mot_de_passe, adresse, telephone) VALUES (?,?,?,?,?)");
            $stmt->execute([$nom, $email, $hash, $adresse, $tel]);
            $newId = $pdo->lastInsertId();
            $_SESSION['client_id']  = $newId;
            $_SESSION['client_nom'] = $nom;
            flash('compte', 'Bienvenue sur EcoMarket, ' . $nom . ' !', 'success');
            redirect(SITE_URL . '/pages/compte.php');
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<section class="section">
<div class="container">
<div class="form-card" style="max-width:680px;">
    <h2 class="form-title">🌱 Créer un compte</h2>

    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"> <?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Nom complet *</label>
                <input type="text" name="nom" class="form-control"
                       value="<?= e($_POST['nom'] ?? '') ?>" required placeholder="Votre nom">
            </div>
            <div class="form-group">
                <label class="form-label">Téléphone</label>
                <input type="tel" name="telephone" class="form-control"
                       value="<?= e($_POST['telephone'] ?? '') ?>" placeholder="+216 XX XXX XXX">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Email *</label>
            <input type="email" name="email" class="form-control"
                   value="<?= e($_POST['email'] ?? '') ?>" required placeholder="votre@email.com">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Mot de passe *</label>
                <input type="password" name="mot_de_passe" class="form-control" required placeholder="Min. 6 caractères">
            </div>
            <div class="form-group">
                <label class="form-label">Confirmer le mot de passe *</label>
                <input type="password" name="mdp_confirm" class="form-control" required placeholder="Répétez le mot de passe">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Adresse de livraison</label>
            <textarea name="adresse" class="form-control" placeholder="Votre adresse complète"><?= e($_POST['adresse'] ?? '') ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary" style="flex:1;">Créer mon compte</button>
        </div>
    </form>
    <p style="text-align:center;margin-top:1.5rem;color:var(--gray-600);">
        Déjà inscrit ? <a href="login.php" style="color:var(--green-dark);font-weight:600;">Se connecter</a>
    </p>
</div>
</div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
