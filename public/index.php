<?php
/**
 * FRONT CONTROLLER - điểm vào duy nhất của website.
 * Mọi request đều đi qua file này, sau đó Router phân phối tới Controller phù hợp (mô hình MVC).
 */
date_default_timezone_set('Asia/Ho_Chi_Minh');   // giờ Việt Nam (cần cho mã giảm giá theo ngày và VNPay)
session_start();

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

// BASE_URL tự tính từ vị trí file, ví dụ: /Thuong_mai_dien_tu
define('BASE_URL', rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/'));

$config = require ROOT_PATH . '/config/config.php';

/** Tự động nạp class theo tên: Router, Database, HomeController... */
spl_autoload_register(function (string $class) {
    foreach (['core', 'controllers', 'models'] as $dir) {
        $file = APP_PATH . "/$dir/$class.php";
        if (is_file($file)) { require $file; return; }
    }
});

require APP_PATH . '/core/helpers.php';

$router = new Router();
require APP_PATH . '/routes.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
