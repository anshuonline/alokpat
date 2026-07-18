<?php
/**
 * Admin Logout
 * 
 * @package Alokpath\Admin
 */

require_once '../config/config.php';

// Clear session
session_unset();
session_destroy();

// Redirect to login
redirect(ADMIN_URL . '/login.php');
