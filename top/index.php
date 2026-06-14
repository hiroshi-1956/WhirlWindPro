<?php
/**
 * WhirlWindProV1.15 - index.php
 */

error_reporting(E_ALL & ~ E_DEPRECATED & ~ E_USER_DEPRECATED & ~ E_NOTICE);
//error_reporting(0);

// 1. 最上部でセッション開始
session_start();

// 2. タイムゾーン設定
date_default_timezone_set('Asia/Tokyo');

// 3. 物理パスとURLの計算
$projectRoot = str_replace('\\', '/', __DIR__);

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'];
$script   = $_SERVER['SCRIPT_NAME'];
$baseDir  = str_replace('/index.php', '', $script);
$baseUrl  = $protocol . '://' . $host . $baseDir;

// 4. オートローダー登録
require_once 'Framework/Core/Autoloader.php';
\Framework\Core\Autoloader::register();

// 5. Containerへの集約（静的情報・インスタンス）
use Framework\Core\Container;

$root = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
Container::set('DOC_ROOT', $root);
Container::set('PROJECT_ROOT', $projectRoot);
Container::set('DEVELOP_URL',  $baseUrl . '/Develop');

// 6. 各種コンテナ・DBの初期化
try {
    // Helper関数読み込み
    if (file_exists('Framework/Core/functions.php')) {
        require_once 'Framework/Core/functions.php';
    }
    
    // Logger
    $logger = new \Framework\Core\Logger();
    Container::set('logger', $logger);
    $logger->debug("--- wwProject System Start ---");
    
    // DB接続 (develop)
    $configDev = require 'Config/db_develop.php';
    $pdoDev = new \PDO($configDev['dsn'], $configDev['username'], $configDev['password'], $configDev['options']);
    $pdoDev->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    Container::setDb_develop($pdoDev);
    
    // DB接続 (product)
//    $configProd = require 'Config/db_product.php';
//    $pdoProd = new \PDO($configProd['dsn'], $configProd['username'], $configProd['password'], $configProd['options']);
//    $pdoProd->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
//    Container::setDb_product($pdoProd);

} catch (\Exception $e) {
    echo "System Initialization Error: " . $e->getMessage();
    exit;
}

// 7. SESSION状態の初期値セット
$_SESSION['LAYOUT_MODE'] = $_SESSION['LAYOUT_MODE'] ?? 'separate';
$_SESSION['ENVIRONMENT'] = $_SESSION['ENVIRONMENT'] ?? 'development';

//echo $baseUrl;
Container::set('BASE_URL',     $baseUrl);

// 8. ルーティング実行
$router = new \Framework\Core\Router();
$router->run();

