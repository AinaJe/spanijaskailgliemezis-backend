<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors; // CodeIgniter iebūvētais Cors filtrs
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;
use App\Filters\AuthFilter; // Jūsu pielāgotais AuthFilter

class Filters extends BaseFilters
{
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class, // Šis ir CodeIgniter\Filters\Cors
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,
        'authFilter'    => AuthFilter::class,
    ];

    public array $required = [
        'before' => [],
        'after'  => [],
    ];

    public array $globals = [
        'before' => [],
        'after' => [
            'toolbar', // Debug rīkjosla
        ],
    ];

    // LABOTS: HTTP metožu reģistrs, un piesaistām CORS filtru
    public array $methods = [
        'GET'    => ['cors'], // JAUNS: CORS visiem GET pieprasījumiem
        'POST'   => ['cors'], // JAUNS: CORS visiem POST pieprasījumiem
        'PUT'    => ['cors'],  // JAUNS: CORS visiem PUT pieprasījumiem
        'DELETE' => ['cors'], // JAUNS: CORS visiem DELETE pieprasījumiem
        'OPTIONS' => ['cors'], // JAUNS: CORS visiem OPTIONS pieprasījumiem (lai gan to apstrādā Routes.php)
    ];

    public array $filters = [];
}