<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function ($routes) {
    // 認證相關路由
    $routes->group('auth', static function ($routes) {
        $routes->post('login', 'AuthController::login');
        $routes->post('resend-verification', 'AuthController::resendVerification');
        $routes->get('verify-email/(:segment)', 'AuthController::verifyEmail/$1');
        $routes->post('register', 'UserController::create');
    });

    // 管理端路由
    $routes->group('admin', ['filter' => 'auth'], static function ($routes) {
        // 據點管理
        $routes->group('locations', static function ($routes) {
            $routes->get('/', 'LocationController::index');
            $routes->get('options', 'LocationController::getOptions');
            $routes->post('/', 'LocationController::create');
            $routes->get('(:num)', 'LocationController::detail/$1');
            $routes->post('(:num)', 'LocationController::edit/$1');
            $routes->delete('(:num)', 'LocationController::delete/$1');
        });

        // 使用者管理
        $routes->group('users', static function ($routes) {
            $routes->get('/', 'UserController::index');
            $routes->get('options', 'UserController::getOptions');
            $routes->post('/', 'UserController::create');
            $routes->get('(:num)', 'UserController::detail/$1');
            $routes->post('(:num)', 'UserController::edit/$1');
            $routes->delete('(:num)', 'UserController::delete/$1');
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
            $routes->get('/', 'StockholderGiftsController::index');
            $routes->get('options', 'StockholderGiftsController::getOptions');
            $routes->post('/', 'StockholderGiftsController::create');
            $routes->put('(:num)', 'StockholderGiftsController::edit/$1');
            $routes->get('(:num)', 'StockholderGiftsController::detail/$1');
            $routes->delete('(:num)','StockholderGiftsController::delete/$1');
            $routes->get('history/(:segment)','StockholderGiftsController::getHistoricalGifts/$1');
        });
    });

    // 公開的檢查功能
    $routes->get('check-account', 'UserController::checkAccount');
});
