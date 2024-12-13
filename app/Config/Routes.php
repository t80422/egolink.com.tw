<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
//$routes->resource('api/news', ['controller' =>'News']);

$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function ($routes) {
    // 認證相關路由
    $routes->group('auth', static function ($routes) {
        $routes->post('login', 'AuthController::login');
        $routes->post('login-admin', 'AuthController::loginAdmin');
        $routes->post('resend-verification', 'AuthController::resendVerification');
    });

    // 管理端路由
    $routes->group('admin', ['filter' => 'auth'], static function ($routes) {
        // 據點管理
        $routes->group('locations',static function ($routes) {
            $routes->get('/', 'LocationController::index');
            $routes->get('options', 'LocationController::getOptions');
            $routes->post('/', 'LocationController::create');
            $routes->get('(:num)', 'LocationController::detail/$1');
            $routes->post('(:num)', 'LocationController::edit/$1');
            $routes->delete('(:num)', 'LocationController::delete/$1');
        });

        // 使用者管理
        $routes->group('users',static function ($routes) {
            $routes->get('/', 'UserController::index');
            $routes->get('options', 'UserController::getOptions');
            $routes->post('/', 'UserController::create');
            $routes->get('(:num)', 'UserController::detail/$1');
            $routes->post('(:num)', 'UserController::edit/$1');
            $routes->delete('(:num)', 'UserController::delete/$1');
        });
    });

    // 客戶端路由
    $routes->group('client', static function ($routes) {
        // 註冊不需要驗證
        $routes->post('register', 'UserController::create');
        
        // 需要驗證的路由
        $routes->group('', ['filter' => 'auth'], static function ($routes) {
            $routes->get('profile', 'UserController::getProfile');
            $routes->post('profile', 'UserController::updateProfile');
        });
    });

    // 公開的檢查功能
    $routes->get('check-account', 'UserController::checkAccount');
});
