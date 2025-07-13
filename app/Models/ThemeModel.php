<?php namespace App\Models;

use CodeIgniter\Model;

class ThemeModel extends Model
{
    protected $table = 'themes';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'summary', 'description'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $returnType    = 'array';

    protected $validationRules = [
        'name'        => 'required|min_length[3]|max_length[255]|is_unique[themes.name,id,{id}]', // is_unique, lai nodrošinātu unikālu nosaukumu
        'summary'     => 'required|min_length[10]',
        'description' => 'required|min_length[10]'
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
}