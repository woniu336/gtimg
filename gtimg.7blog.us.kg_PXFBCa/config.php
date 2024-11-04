<?php
// 默认管理员账户
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123');

// 检查用户是否已登录
function isLoggedIn() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// 检查是否为管理员
function isAdmin() {
    return isLoggedIn();
} 