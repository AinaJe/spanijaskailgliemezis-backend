<?php namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Firebase\JWT\JWT; // Importējam JWT bibliotēku
use Firebase\JWT\Key; // Importējam Key klasi no JWT bibliotēkas

class AuthFilter implements FilterInterface
{
    /**
     * Izpildās pirms katra pieprasījuma.
     * Šeit mēs pārbaudīsim JWT tokenu.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Iegūstam JWT slepeno atslēgu no .env faila
        $key = getenv('JWT_SECRET_KEY');

        // Mēģinām iegūt autorizācijas galveni (Authorization header) no pieprasījuma
        $header = $request->getServer('HTTP_AUTHORIZATION');

        // Ja galvene nav atrasta, atgriežam 401 Unauthorized atbildi
        if (!$header) {
            return service('response')->setStatusCode(401)->setJSON(['error' => 'Nav atļauts: Nav autorizācijas tokena.']);
        }

        // Sadalām galveni, lai iegūtu tikai tokenu (formā "Bearer <token>")
        $token = explode(' ', $header)[1]; // Pieņemam, ka tokens ir aiz vārda 'Bearer'

        try {
            // Dekodējam JWT tokenu
            // Ja dekodēšana neizdodas (nederīgs paraksts, beidzies derīguma termiņš utt.), tiks izmests izņēmums
            $decoded = JWT::decode($token, new Key($key, 'HS256'));

            // Ja nepieciešams, varam pievienot dekodētos lietotāja datus pieprasījuma objektam
            // Šie dati būs pieejami kontrolierī: $this->request->decoded_user->uid
            $request->decoded_user = $decoded;

        } catch (\Exception $e) {
            // Ja tokena dekodēšana neizdevās, atgriežam 401 Unauthorized atbildi
            return service('response')->setStatusCode(401)->setJSON(['error' => 'Nav atļauts: Nederīgs autorizācijas token.']);
        }
    }

    /**
     * Izpildās pēc katra pieprasījuma.
     * Šajā gadījumā nekas nav jādara.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Šeit var veikt darbības pēc pieprasījuma apstrādes, piemēram, pievienot galvenes.
    }
}