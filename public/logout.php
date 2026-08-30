<?php
/**
 * Vishal Web Studio - Safe Logout
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';

logout();
set_flash('info', 'You have been safely signed out.');
header('Location: ' . BASE_URL . '/public/login.php');
exit;
