<?php
// 默认管理员账户
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123');

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
    $replacement = "define('GTIMG_TOKEN', 'userid=22510990; omaccesstoken=00885d883d95768bbb3f8045c0958c64a7ae9ca0b87120c67b312c0656b3bca120bd8adea07327c6f9aca41d0e7df9ccbff7d8dfd04b3407dcf8d32d405c314560uf; omtoken=00885d883d95768bbb3f8045c0958c64a7ae9ca0b87120c67b312c0656b3bca120bd8adea07327c6f9aca41d0e7df9ccbff7d8dfd04b3407dcf8d32d405c314560uf; srcaccessToken=86_rP0zzW2Xvkwa0UmbfCqrM4-pBT5ifjCWSLZC6cBdl0Djuqat6l-lUWdzZmofMZDhhufwnNbuQbB2dRMl1Ykg-gjM4G090PXwcRzi6HBlYcc');";
    
    $newContent = preg_replace($pattern, $replacement, $content);
    
    return file_put_contents($configFile, $newContent);
}

function getToken() {
    return GTIMG_TOKEN;
} 