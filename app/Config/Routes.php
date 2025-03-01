<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function ($routes) {
    // 認證相關路由
    $routes->group('auth', static function ($routes) {
        $routes->get('verify-reset/(:segment)', 'AuthController::verifyResetToken/$1');
        $routes->post('login', 'AuthController::login');
        $routes->post('resend-verification', 'AuthController::resendVerification');
        $routes->post('register', 'UserController::create');
        $routes->post('request-reset', 'AuthController::requestPasswordReset');
        $routes->post('reset-password/(:segment)', 'AuthController::resetPassword/$1');
        $routes->get('verify-email/(:segment)', 'AuthController::verifyEmail/$1');
    });

    // 管理端路由
    $routes->group('admin', ['filter' => ['auth', 'featureAuth']], static function ($routes) {
        // 據點管理
        $routes->group('locations', static function ($routes) {
            $routes->get('/', 'LocationController::index');
            $routes->post('/', 'LocationController::create');
            $routes->get('(:num)', 'LocationController::detail/$1');
            $routes->post('(:num)', 'LocationController::edit/$1');
            $routes->delete('(:num)', 'LocationController::delete/$1');
        });

        // 會員管理
        $routes->group('users', static function ($routes) {
            $routes->get('/', 'UserController::index');
            $routes->get('options', 'UserController::getOptions');
            $routes->get('(:num)', 'UserController::detail/$1');
            $routes->get('groupOptions', 'UserController::getGroupAccountOptions');
            $routes->post('/', 'UserController::create');
            $routes->put('(:num)', 'UserController::edit/$1');
            $routes->delete('(:num)', 'UserController::delete/$1');
        });

        // 子帳號
        $routes->group('subAccount', static function ($routes) {
            $routes->get('(:num)', 'SubAccountController::getUserSubAccounts/$1');
            $routes->get('detail/(:num)', 'SubAccountController::detail_Admin/$1');
            $routes->put('(:num)', 'SubAccountController::edit_admin/$1');
        });

        // 最新消息
        $routes->group('news', static function ($routes) {
            $routes->get('(:num)', 'NewsController::detail/$1');
            $routes->post('/', 'NewsController::create');
            $routes->put('(:num)', 'NewsController::edit/$1');
            $routes->delete('(:num)', 'NewsController::delete/$1');
        });

        // 常見問題
        $routes->group('qa', static function ($routes) {
            $routes->get('(:num)', 'QAController::detail/$1');
            $routes->post('/', 'QAController::create');
            $routes->put('(:num)', 'QAController::edit/$1');
            $routes->delete('(:num)', 'QAController::delete/$1');
        });

        // 進貨作業
        $routes->group('purchase', static function ($routes) {
            $routes->get('/', 'PurchaseController::index');
            $routes->get('(:num)', 'PurchaseController::detail/$1');
            $routes->post('/', 'PurchaseController::create');
            $routes->put('(:num)', 'PurchaseController::edit/$1');
            $routes->delete('(:num)', 'PurchaseController::delete/$1');
        });

        // 倉庫管理
        $routes->group('product', static function ($routes) {
            $routes->get('/', 'ProductController::index');
            $routes->get('(:num)', 'ProductController::detail/$1');
            $routes->get('inventory-logs', 'ProductController::inventoryLogs');
            $routes->post('/', 'ProductController::create');
            $routes->post('(:num)', 'ProductController::edit/$1');
            $routes->delete('(:num)', 'ProductController::delete/$1');
        });

        // 出貨作業
        $routes->group('shipment', static function ($routes) {
            $routes->get('/', 'ShipmentController::index'); // 列表
            $routes->get('(:num)', 'ShipmentController::detail/$1'); // 詳細
            $routes->get('orders', 'ShipmentController::getShippableUsers'); // 取得可出貨會員列表
            $routes->get('order-summary/(:num)', 'ShipmentController::getOrderSummary/$1'); // 取得用戶的委託紀念品統計
            $routes->post('/', 'ShipmentController::create');
            $routes->put('(:num)', 'ShipmentController::edit/$1');
            $routes->delete('(:num)', 'ShipmentController::delete/$1');
        });

        // 權限管理
        $routes->group('permission', static function ($routes) {
            $routes->get('/', 'AuthController::getFeatures'); // 取得所有功能
            $routes->get('(:num)', 'AuthController::getRoleFeatures/$1'); // 取得角色功能權限
            $routes->put('(:num)', 'AuthController::updateRoleFeatures/$1'); // 更新角色功能權限
        });

        // 股東會資訊
        $routes->group('stock', static function ($routes) {
            $routes->post('/', 'StockholderGiftsController::create');
        });
    });

    // 客戶端路由
    $routes->group('client', ['filter' => 'auth'], static function ($routes) {
        // 使用者管理
        $routes->group('users', static function ($routes) {
            $routes->get('/', 'UserController::getProfile');
            $routes->post('edit', 'UserController::editProfile');
            $routes->post('change-password', 'UserController::changePassword');
        });

        // 股東會資訊
        $routes->group('stockholderGifts', static function ($routes) {
            $routes->get('(:num)', 'StockholderGiftsController::detail/$1');
            $routes->put('(:num)', 'StockholderGiftsController::edit/$1');
            $routes->delete('(:num)', 'StockholderGiftsController::delete/$1');
        });

        // 子帳號
        $routes->group('subAccount', static function ($routes) {
            $routes->get('/', 'SubAccountController::index');
            $routes->get('(:num)', 'SubAccountController::detail_Client/$1');
            $routes->post('/', 'SubAccountController::create');
            $routes->post('upload/(:num)', 'SubAccountController::upload/$1');
            $routes->put('(:num)', 'SubAccountController::edit/$1');
            $routes->delete('(:num)', 'SubAccountController::delete/$1');
        });

        // 委託
        $routes->group('orders', static function ($routes) {
            $routes->get('/', 'OrderController::index');
            $routes->get('detail', 'OrderController::detail');
            $routes->get('conditions', 'OrderController::getConditions');
            $routes->post('batch', 'OrderController::batchCreate');
            $routes->put('(:num)', 'OrderController::edit/$1');
            $routes->delete('(:num)', 'OrderController::delete/$1');
        });

        // 我的出貨單
        $routes->group('myShipment', static function ($routes) {
            $routes->get('/', 'ShipmentController::getMyShipmentList');
            $routes->get('(:num)', 'ShipmentController::detail/$1');
        });
    });

    // E股投路由
    $routes->group('autoVote', static function ($routes) {
        $routes->post('login', 'AuthController::login_autoVote'); // 登入
        $routes->get('(:num)', 'SubAccountController::getAutoVoteAccounts/$1'); // 取得子帳號資訊
        $routes->post('vote-img/(:num)', 'OrderController::uploadVoteImg/$1'); // 投票圖片上傳
    });

    // 公開路由
    $routes->get('check-account', 'UserController::checkAccount');  // 檢查帳號重複
    $routes->get('stockholder-gifts', 'StockholderGiftsController::index'); // 股東會資訊列表
    $routes->get('history', 'StockholderGiftsController::getHistoricalGifts'); // 歷年紀念品
    $routes->get('options', 'StockholderGiftsController::getOptions'); // 股東會資訊搜尋選項
    $routes->get('news', 'NewsController::index'); // 最新消息列表
    $routes->get('locationOptions', 'LocationController::getOptions'); // 營業據點選單
    $routes->get('qa', 'QAController::index'); // 常見問題列表
    $routes->get('locations', 'LocationController::index'); // 據點列表
    $routes->get('stockOpions', 'StockholderGiftsController::getSGOptions'); // 股東會選項
    $routes->get('productOptions/(:num)', 'ProductController::getProductOptions/$1'); // 紀念品選項
});
