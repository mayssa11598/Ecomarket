<?php
// ============================================================
//  EcoMarket – Configuration & Connexion PDO
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'ecomarket');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('SITE_NAME', 'EcoMarket');
define('SITE_URL', 'http://localhost/ecomarket');
define('PER_PAGE', 9); // Produits par page

/**
 * Retourne la connexion PDO (singleton)
 */
function getPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('<div style="font-family:monospace;color:red;padding:2rem;">
                 ❌ Erreur de connexion à la base de données : ' . htmlspecialchars($e->getMessage()) . '
                 </div>');
        }
    }
    return $pdo;
}

// Démarrage de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -------------------------------------------------------
// Helpers génériques
// -------------------------------------------------------

/** Nettoyer une valeur pour l'affichage HTML */
function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

/** Redirection propre */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/** Message flash (stocké en session) */
function flash(string $key, string $msg = '', string $type = 'success'): string {
    if ($msg !== '') {
        $_SESSION['flash'][$key] = ['msg' => $msg, 'type' => $type];
        return '';
    }
    if (isset($_SESSION['flash'][$key])) {
        $f = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        $icon = $f['type'] === 'success' ? '✅' : '❌';
        return '<div class="alert alert-' . $f['type'] . '">' . $icon . ' ' . e($f['msg']) . '</div>';
    }
    return '';
}

/** Vérifie si le client est connecté */
function isLoggedIn(): bool {
    return isset($_SESSION['client_id']);
}

/** Retourne l'ID du client connecté */
function clientId(): ?int {
    return $_SESSION['client_id'] ?? null;
}

/** Vérifie si l'utilisateur connecté est admin */
function isAdmin(): bool {
    return isset($_SESSION['client_id']) && ($_SESSION['is_admin'] ?? 0) === 1;
}

/**
 * Protège une page admin : redirige vers login admin si non connecté ou non admin.
 * À appeler au tout début de chaque fichier /admin/ (sauf login.php).
 */
function requireAdmin(): void {
    if (!isAdmin()) {
        $_SESSION['admin_redirect'] = $_SERVER['REQUEST_URI'] ?? '';
        redirect(SITE_URL . '/admin/login.php');
    }
}

/** Nombre d'articles dans le panier */
function cartCount(): int {
    $panier = $_SESSION['panier'] ?? [];
    return array_sum(array_column($panier, 'qte'));
}

/** Formater un prix */
function prix(float $p): string {
    return number_format($p, 2, ',', ' ') . ' DT';
}

/** Statut commande en badge lisible */
function statutBadge(string $s): string {
    $map = [
        'en_attente' => ['En attente',  'warning'],
        'validee'    => ['Validée',     'info'],
        'expediee'   => ['Expédiée',    'primary'],
        'livree'     => ['Livrée',      'success'],
        'annulee'    => ['Annulée',     'danger'],
    ];
    $d = $map[$s] ?? [$s, 'secondary'];
    return '<span class="badge badge-' . $d[1] . '">' . $d[0] . '</span>';
}

/** Statut expédition */
function statutExpBadge(string $s): string {
    $map = [
        'preparation' => ['Préparation', 'warning'],
        'en_transit'  => ['En transit',  'info'],
        'livre'       => ['Livré',       'success'],
        'echec'       => ['Échec',       'danger'],
    ];
    $d = $map[$s] ?? [$s, 'secondary'];
    return '<span class="badge badge-' . $d[1] . '">' . $d[0] . '</span>';
}

/** Pagination : retourne LIMIT/OFFSET + infos */
function paginate(int $total, int $page, int $perPage = PER_PAGE): array {
    $pages   = max(1, (int)ceil($total / $perPage));
    $page    = max(1, min($page, $pages));
    $offset  = ($page - 1) * $perPage;
    return ['page' => $page, 'pages' => $pages, 'offset' => $offset, 'total' => $total];
}
// -------------------------------------------------------
// Gestion des images produits (URLs externes)
// -------------------------------------------------------
define('IMG_DEFAULT', 'https://placehold.co/400x300/d5f5e3/2ecc71?text=EcoMarket');

/**
 * Retourne l'URL d'image à afficher pour un produit.
 * Accepte une URL http(s) complète, ou vide → placeholder.
 */
function imgUrl(string $image): string {
    $image = trim($image);
    if ($image === '' || $image === 'default.jpg' || $image === 'default.svg') {
        return IMG_DEFAULT;
    }
    if (filter_var($image, FILTER_VALIDATE_URL) && str_starts_with($image, 'http')) {
        return $image;
    }
    return IMG_DEFAULT;
}

/**
 * Valide une URL d'image soumise via formulaire.
 * Retourne ['ok' => bool, 'url' => string, 'error' => string]
 */
function validateImageUrl(string $url, string $oldUrl = ''): array {
    $url = trim($url);
    if ($url === '') {
        return ['ok' => true, 'url' => $oldUrl];
    }
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return ['ok' => false, 'url' => '', 'error' => 'URL invalide.'];
    }
    if (!preg_match('#^https?://#i', $url)) {
        return ['ok' => false, 'url' => '', 'error' => 'L\'URL doit commencer par http:// ou https://'];
    }
    return ['ok' => true, 'url' => $url, 'error' => ''];
}
