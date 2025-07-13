<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Noklusējuma maršruts (sveiciena lapa)
$routes->get('/', 'Home::index');

// API maršruti
// NEPIEVIEŅOT 'filter' => 'cors' ŠEIT! Mēs to apstrādājam ar globālajiem $methods filtriem un OPTIONS maršrutu.
$routes->group('api', ['namespace' => 'App\Controllers\Api'], function($routes) {

    // JAUNS: Apstrādā OPTIONS (preflight) pieprasījumus visiem API maršrutiem.
    // ŠIS IR ĻOTI SVARĪGS CORS darbībai!
    $routes->options('(:any)', 'BaseController::optionsResponse'); // 'BaseController' bez 'Api' namespace

    // Publiskie API maršruti (piekļūstami bez autentifikācijas)
    $routes->get('authors', 'Author::index');
    $routes->get('authors/(:num)', 'Author::show/$1');
    $routes->get('themes', 'Theme::index');
    $routes->get('themes/(:num)', 'Theme::show/$1');
    $routes->get('cards', 'Card::index');
    $routes->get('cards/(:num)', 'Card::show/$1');
    $routes->get('articles', 'Article::index');
    $routes->get('articles/(:num)', 'Article::show/$1');
    $routes->get('videos', 'Video::index');
    $routes->get('videos/(:num)', 'Video::show/$1');

    // Administratora autentifikācijas maršruts (publisks, jo pieteikšanās notiek bez tokena)
    $routes->post('admin/login', 'Auth::login');

    // Aizsargātie API maršruti (nepieciešams autentifikācijas tokens)
    // Šai grupai piemērojam 'authFilter'
    $routes->group('', ['filter' => 'authFilter'], function($routes) {

        // Autoru CRUD maršruti (bez index un show, jo tie jau definēti augstāk kā publiski)
        $routes->post('authors', 'Author::create');
        $routes->put('authors/(:num)', 'Author::update/$1');
        $routes->delete('authors/(:num)', 'Author::delete/$1');

        // Tēmu CRUD maršruti
        $routes->post('themes', 'Theme::create');
        $routes->put('themes/(:num)', 'Theme::update/$1');
        $routes->delete('themes/(:num)', 'Theme::delete/$1');

        // Kartīšu (ieteikumu) CRUD maršruti
        $routes->post('cards', 'Card::create');
        $routes->put('cards/(:num)', 'Card::update/$1');
        $routes->delete('cards/(:num)', 'Card::delete/$1');

        // Kartīšu attēlu CRUD maršruti
        $routes->post('card_images', 'CardImage::create');
        $routes->put('card_images/(:num)', 'CardImage::update/$1');
        $routes->delete('card_images/(:num)', 'CardImage::delete/$1');

        // Rakstu CRUD maršruti
        $routes->post('articles', 'Article::create');
        $routes->put('articles/(:num)', 'Article::update/$1');
        $routes->delete('articles/(:num)', 'Article::delete/$1');

        // Video CRUD maršruti
        $routes->post('videos', 'Video::create');
        $routes->put('videos/(:num)', 'Video::update/$1');
        $routes->delete('videos/(:num)', 'Video::delete/$1');
    });
});