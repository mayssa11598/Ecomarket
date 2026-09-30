<?php
// ============================================================
//  EcoMarket – Déconnexion Admin
// ============================================================
require_once __DIR__ . '/../includes/config.php';

// Détruire uniquement les variables de session admin
// (on garde la session client si elle existait séparément)
unset($_SESSION['client_id'], $_SESSION['client_nom'], $_SESSION['is_admin'], $_SESSION['admin_redirect']);
session_destroy();

redirect(SITE_URL . '/admin/login.php');
