<?php
// ============================================================
//  EcoMarket – Connexion Administrateur
// ============================================================
require_once __DIR__ . '/../includes/config.php';

// Déjà connecté en tant qu'admin → rediriger
if (isAdmin()) {
    redirect(SITE_URL . '/admin/produits.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mot_de_passe'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresse email invalide.';
    } elseif ($mdp === '') {
        $errors[] = 'Mot de passe requis.';
    } else {
        $stmt = getPDO()->prepare("SELECT * FROM clients WHERE email = ? AND is_admin = 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($mdp, $admin['mot_de_passe'])) {
            // ── Connexion réussie ──
            session_regenerate_id(true); // Sécurité : nouvel ID de session
            $_SESSION['client_id']  = $admin['id'];
            $_SESSION['client_nom'] = $admin['nom'];
            $_SESSION['is_admin']   = 1;

            // Rediriger vers la page demandée initialement (ou produits par défaut)
            $redirect = $_SESSION['admin_redirect'] ?? (SITE_URL . '/admin/produits.php');
            unset($_SESSION['admin_redirect']);
            redirect($redirect);
        } else {
            // Message volontairement vague pour ne pas révéler si l'email existe
            $errors[] = 'Identifiants incorrects ou accès non autorisé.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration – EcoMarket</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a2620 0%, #2c3e35 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .admin-login-wrap {
            width: 100%;
            max-width: 440px;
        }
        .admin-login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .admin-login-logo .eco {
            font-size: 3rem;
            display: block;
            margin-bottom: .5rem;
        }
        .admin-login-logo h1 {
            font-family: var(--font-head);
            color: #fff;
            font-size: 1.8rem;
            margin-bottom: .25rem;
        }
        .admin-login-logo p {
            color: rgba(255,255,255,.5);
            font-size: .9rem;
        }
        .admin-login-card {
            background: #fff;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 24px 60px rgba(0,0,0,.4);
        }
        .admin-login-card h2 {
            font-family: var(--font-head);
            font-size: 1.4rem;
            color: var(--black);
            margin-bottom: 1.5rem;
            padding-bottom: .75rem;
            border-bottom: 2px solid var(--green-light);
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .badge-admin {
            display: inline-block;
            background: var(--green);
            color: #fff;
            font-size: .7rem;
            font-weight: 700;
            padding: .2rem .6rem;
            border-radius: 50px;
            letter-spacing: .05em;
            font-family: var(--font-body);
        }
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        .back-link a {
            color: rgba(255,255,255,.5);
            font-size: .88rem;
            transition: color .2s;
        }
        .back-link a:hover { color: var(--green); }
        .demo-box {
            background: var(--green-xlight);
            border: 1px solid var(--green-light);
            border-radius: var(--radius);
            padding: .75rem 1rem;
            font-size: .83rem;
            color: var(--gray-600);
            margin-top: 1.25rem;
            line-height: 1.7;
        }
        .demo-box strong { color: var(--green-dark); }
    </style>
</head>
<body>

<div class="admin-login-wrap">

    <div class="admin-login-logo">
        <span class="eco">🌿</span>
        <h1>EcoMarket</h1>
        <p>Espace d'administration sécurisé</p>
    </div>

    <div class="admin-login-card">
        <h2>
            Connexion Admin
            <span class="badge-admin">ADMIN</span>
        </h2>

        <?php if (isset($_SESSION['admin_redirect'])): ?>
            <div class="alert alert-info">
                 Vous devez être connecté en tant qu'administrateur pour accéder à cette page.
            </div>
        <?php endif; ?>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-error">❌ <?= e($err) ?></div>
        <?php endforeach; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Email administrateur</label>
                <input type="email" name="email" class="form-control" required autofocus
                       value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="mot_de_passe" class="form-control" required
                       placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;padding:.85rem;font-size:1rem;margin-top:.5rem;">
                 Accéder au back-office
            </button>
        </form>
    </div>

    <div class="back-link">
        <a href="<?= SITE_URL ?>/index.php">← Retour au site EcoMarket</a>
    </div>

</div>

</body>
</html>
