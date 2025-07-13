<?php namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController; // Noņemam, jo mantosim no BaseController
use App\Controllers\BaseController; // JAUNS: Importējam BaseController
use App\Models\UserModel; // Importējam lietotāja modeli
use Firebase\JWT\JWT; // Importējam JWT bibliotēku
use Firebase\JWT\Key; // Importējam Key klasi no JWT bibliotēkas

class Auth extends BaseController // LABOTS: Manto no BaseController
{
    // Protected modelName vairs nav nepieciešams, jo modeli inicializēsim konstruktorā
    protected $userModel; // JAUNS: Lietotāja modeļa īpašība

    // JAUNS: Konstruktors, lai inicializētu modeli
    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Apstrādā administratora pieteikšanos.
     * Atbilst POST /api/admin/login
     */
    public function login()
    {
        // Definējam validācijas noteikumus pieteikšanās datiem
        $rules = [
            'username' => 'required|alpha_numeric_space|min_length[3]|max_length[50]',
            'password' => 'required|min_length[8]|max_length[255]|validateUser[username,password]',
        ];

        // Pielāgotie kļūdu ziņojumi validācijai
        $errors = [
            'password' => [
                'validateUser' => 'Nepareizs lietotājvārds vai parole.'
            ]
        ];

        // Veicam datu validāciju, izmantojot BaseController validate() metodi
        if (!$this->validate($rules, $errors)) {
            // Atgriežam validācijas kļūdas JSON formātā
            // $this->response ir pieejams no BaseController
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'errors' => $this->validator->getErrors(),
                'message' => 'Validācijas kļūda.'
            ]);
        }

        // Ja validācija veiksmīga, iegūstam lietotāja datus
        // $this->request->getVar() ir pieejams no BaseController
        $user = $this->userModel->where('username', $this->request->getVar('username'))->first();

        // Kļūdas apstrādei, ja kaut kādā veidā lietotājs nav atrasts (lai gan validateUser to jau pārbauda)
        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 401,
                'message' => 'Nepareizs lietotājvārds vai parole.'
            ]);
        }

        // Iegūstam JWT slepeno atslēgu no .env faila
        $key = getenv('JWT_SECRET_KEY');

        // JWT Payload (tokena saturs)
        $iat = time(); // Tokena izdošanas laiks (issued at)
        $exp = $iat + 3600; // Tokena derīguma termiņš (expiration time) - 1 stunda (3600 sekundes)
        $payload = [
            'iss' => 'CodeIgniter App', // Izdevējs
            'aud' => 'Frontend Application', // Saņēmējs (auditorija)
            'iat' => $iat,
            'exp' => $exp,
            'uid' => $user['id'], // Lietotāja ID
            'username' => $user['username'], // Lietotājvārds
            'role' => $user['role'], // Lietotāja loma (piem., 'admin')
        ];

        // Ģenerējam JWT tokenu
        $token = JWT::encode($payload, $key, 'HS256'); // HS256 ir hešošanas algoritms

        // Atgriežam veiksmīgu atbildi ar tokenu JSON formātā
        return $this->response->setStatusCode(200)->setJSON([
            'status' => 200,
            'message' => 'Pieteikšanās veiksmīga',
            'token' => $token
        ]);
    }
}