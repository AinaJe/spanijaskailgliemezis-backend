<?php namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = ['username', 'password_hash', 'role'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $returnType    = 'array';

    protected $validationRules = [
        'username'      => 'required|alpha_numeric_space|min_length[3]|max_length[50]|is_unique[users.username,id,{id}]',
        'password_hash' => 'required|min_length[6]|max_length[255]', // Min_length atkarīgs no heša garuma
        'role'          => 'required|alpha|max_length[50]'
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;

    // Pirms saglabāšanas hešot paroli (ja nepieciešams, lai modeli izmantotu tieši lietotāju izveidei)
    // protected $beforeInsert = ['hashPassword'];
    // protected $beforeUpdate = ['hashPassword'];

    // protected function hashPassword(array $data)
    // {
    //     if (isset($data['data']['password'])) {
    //         $data['data']['password_hash'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
    //         unset($data['data']['password']); // Noņem nehešoto paroli
    //     }
    //     return $data;
    // }
}