<?php
/**
 * Admin Index - Redirect to login or dashboard
 */
require_once '../config/config.php';

if (isLoggedIn()) {
    redirect(ADMIN_URL . '/dashboard.php');
} else {
    redirect(ADMIN_URL . '/login.php');
}
