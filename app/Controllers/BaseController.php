<?php namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
// JAUNS: Importējam Config\CORS klasi
use Config\CORS;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 * class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var list<string>
     */
    protected $helpers = [];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    // protected $session; // Atkomentējiet, ja izmantojat sesijas

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.

        // E.g.: $this->session = service('session');
    }

    /**
     * Apstrādā CORS preflight OPTIONS pieprasījumus.
     * Šī metode tiek izsaukta, kad pārlūkprogramma nosūta OPTIONS pieprasījumu,
     * lai pārbaudītu, vai reālais pieprasījums ir atļauts.
     *
     * @return ResponseInterface
     */
    public function optionsResponse(): ResponseInterface
    {
        $response = $this->response;
        $config = config('CORS'); // Ielādējam CORS konfigurāciju

        // Pārliecinieties, ka $config ir objekts un tam ir nepieciešamās īpašības
        if (!is_object($config) || !isset($config->allowedOrigins)) {
            // Šeit varētu būt uzlabota kļūdu apstrāde vai žurnālēšana
            return $response->setStatusCode(500)->setJSON(['error' => 'Server Error: CORS config not loaded or invalid.']);
        }

        // Iestatām Access-Control-Allow-Origin galveni
        $origin = $this->request->getHeaderLine('Origin');
        if (in_array($origin, $config->allowedOrigins) || in_array('*', $config->allowedOrigins)) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
        } else {
            // Ja origins nav atļauts, varam atgriezt 403 Forbidden
            return $response->setStatusCode(403)->setJSON(['message' => 'CORS: Origin not allowed']);
        }

        // Iestatām citas CORS galvenes
        $response->setHeader('Access-Control-Allow-Headers', implode(', ', $config->allowedHeaders))
                 ->setHeader('Access-Control-Allow-Methods', implode(', ', $config->allowedMethods))
                 ->setHeader('Access-Control-Allow-Credentials', $config->allowCredentials ? 'true' : 'false')
                 ->setHeader('Access-Control-Max-Age', $config->maxAge);

        // Vienmēr atgriežam 200 OK OPTIONS pieprasījumam
        return $response->setStatusCode(200);
    }
}