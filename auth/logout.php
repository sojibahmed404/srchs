<?php
/**
 * Sirajganj Road Chowrasta High School - Logout Handler
 */

require_once dirname(__DIR__) . '/includes/auth.php';

logoutUser();
initSession();
setFlash('success', 'You have been safely signed out.');
redirect(url('auth/login.php'));
