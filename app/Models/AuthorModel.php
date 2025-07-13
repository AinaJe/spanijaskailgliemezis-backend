<?php namespace App\Models;

use CodeIgniter\Model;

class AuthorModel extends Model
{
    protected $table = 'authors'; // Datubāzes tabulas nosaukums
    protected $primaryKey = 'id'; // Primārās atslēgas kolonna

    // Kolonnas, kurām atļauts veikt mass assignment (ievietošanu/atjaunināšanu)
    protected $allowedFields = ['name'];

    // Automātiski apstrādā created_at un updated_at laukus
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Atgriešanas tips: 'array' (masīvs) vai 'object' (objekts)
    protected $returnType    = 'array';

    // Datu validācijas noteikumi (papildu, bet ieteicams)
    protected $validationRules = [
        'name' => 'required|min_length[3]|max_length[255]'
    ];
    protected $validationMessages = [];
    protected $skipValidation = false; // Vai izlaist validāciju
}