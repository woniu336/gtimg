<?php
// 默认管理员账户
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123');

// 腾讯图床Token配置
define('GTIMG_TOKEN', '');  // 默认为空

// 检查用户是否已登录
function isLoggedIn() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// 检查是否为管理员
function isAdmin() {
    return isLoggedIn();
}

// 获取和更新Token
function updateToken($newToken) {
    $configFile = __DIR__ . '/config.php';
    $content = file_get_contents($configFile);
    
    // 更新GTIMG_TOKEN的值
    $pattern = "/(define\('GTIMG_TOKEN',\s*').*('\);)/";
    $replacement = "define('GTIMG_TOKEN', '" . addslashes($newToken) . "');";
    
    $newContent = preg_replace($pattern, $replacement, $content);
    
    return file_put_contents($configFile, $newContent);
}

function getToken() {
    return GTIMG_TOKEN;
} 