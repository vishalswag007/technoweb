<?php
/**
 * Vishal Web Studio - Revert Impersonation to Super Admin
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';

if (is_impersonating()) {
    revert_impersonation();
    set_flash('success', 'Returned back to Super Admin Console.');
    header('Location: ' . BASE_URL . '/super-admin/clients.php');
    exit;
}

header('Location: ' . BASE_URL . '/index.php');
exit;
