<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class CORS extends BaseConfig
{
    /**
     * DEFAULT CORS CONFIGURATION
     *
     * This array defines the default CORS configuration that
     * CodeIgniter's internal CORS service expects.
     *
     * @var array{
     * allowedOrigins: list<string>,
     * allowedOriginsPatterns: list<string>,
     * allowedMethods: list<string>,
     * allowedHeaders: list<string>,
     * exposedHeaders: list<string>,
     * supportsCredentials: bool,
     * maxAge: int,
     * }
     */
    public array $default = [
        'allowedOrigins'         => ['http://localhost:5173'], // SVARĪGI: Jūsu React frontend URL!
        'allowedOriginsPatterns' => [], // Varat pievienot regex modeļus, ja nepieciešams
        'allowedMethods'         => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowedHeaders'         => ['Origin', 'X-Requested-With', 'Content-Type', 'Accept', 'Authorization'],
        'exposedHeaders'         => [],
        'supportsCredentials'    => true,
        'maxAge'                 => 3600, // 1 stunda
    ];
}