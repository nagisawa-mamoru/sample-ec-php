<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function (RouteCollection $routes) {
    // CORSプリフライト(OPTIONS)を受けるためのルート。
    // 実際のレスポンスはCorsフィルタ(app/Filters/Cors.php)のbefore()が返す。
    $routes->options('(:any)', static fn () => service('response')->setStatusCode(204));

    $routes->get('products', 'ProductController::index');
    $routes->get('products/(:num)', 'ProductController::show/$1');

    $routes->get('orders', 'OrderController::index');
    $routes->get('orders/(:num)', 'OrderController::show/$1');
    $routes->post('orders', 'OrderController::create');
});
