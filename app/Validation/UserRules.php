<?php namespace App\Validation;

use App\Models\UserModel; // Importējam lietotāja modeli, lai piekļūtu lietotāja datiem

class UserRules
{
    /**
     * Pielāgots validācijas noteikums lietotāja vārda un paroles pārbaudei.
     *
     * @param string $str  - Ienākošā virkne (šajā gadījumā parole, bet netiek tieši izmantota, jo visa loģika ir `data` masīvā).
     * @param string $fields - Lauki, kas tiek padoti no validācijas noteikuma (piem., 'username,password').
     * @param array  $data - Visi validējamie dati no pieprasījuma (satur lietotājvārdu un paroli).
     * @return bool
     */
    public function validateUser(string $str, string $fields, array $data): bool
    {
        // Izveidojam UserModel instanci, lai piekļūtu datubāzei
        $model = new UserModel();

        // Atrodam lietotāju pēc lietotājvārda
        $user = $model->where('username', $data['username'])->first();

        // Ja lietotājs nav atrasts, atgriežam 'false' (validācija neizdevās)
        if (!$user) {
            return false;
        }

        // Pārbaudām, vai ienākošā parole sakrīt ar hešoto paroli datubāzē
        // Izmantojam password_verify(), lai salīdzinātu nehešoto paroli ar hešu
        return password_verify($data['password'], $user['password_hash']);
    }
}