<?php
/**
 * SkillSwap Wallet — Logout
 */
require_once __DIR__ . '/includes/auth.php';
session_destroy();
header('Location: ' . BASE_URL . '/login.php?msg=logout');
exit;
