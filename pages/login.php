<?php
// ============================================================
//  EcoMarket – Connexion client
// ============================================================
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Connexion';
$pdo = getPDO();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mot_de_passe'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresse email invalide.';
    } elseif ($mdp === '') {
        $errors[] = 'Mot de passe requis.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM clients WHERE email = ?");
        $stmt->execute([$email]);
        $client = $stmt->fetch();
        if ($client && password_verify($mdp, $client['mot_de_passe'])) {
            $_SESSION['client_id']  = $client['id'];
            $_SESSION['client_nom'] = $client['nom'];
            $_SESSION['is_admin']   = (int)($client['is_admin'] ?? 0);
            $redirect = $_GET['redirect'] ?? '';
            redirect(SITE_URL . '/pages/' . ($redirect ?: 'compte.php'));
        } else {
            $errors[] = 'Email ou mot de passe incorrect.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<section class="section">
<div class="container">
<div class="form-card">
    <h2 class="form-title"> Connexion</h2>

    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error"> <?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label class="form-label">Adresse email</label>
            <input type="email" name="email" class="form-control"
                   value="<?= e($_POST['email'] ?? '') ?>" required placeholder="votre@email.com">
        </div>
        <div class="form-group">
            <label class="form-label">Mot de passe</label>
            <input type="password" name="mot_de_passe" class="form-control" required placeholder="••••••••">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary" style="flex:1;">Se connecter</button>
        </div>
    </form>
    <p style="text-align:center;margin-top:1.5rem;color:var(--gray-600);">
        Pas encore de compte ? <a href="inscription.php" style="color:var(--green-dark);font-weight:600;">S'inscrire</a>
    </p>
</div>
</div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
