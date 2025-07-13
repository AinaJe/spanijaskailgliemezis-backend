<?php namespace App\Models;

use CodeIgniter\Model;

class CardModel extends Model
{
    protected $table = 'cards';
    protected $primaryKey = 'id';
    protected $allowedFields = ['theme_id', 'title', 'summary', 'description', 'author_id', 'order_index'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $returnType    = 'array';

    protected $validationRules = [
        'theme_id'    => 'required|integer',
        'title'       => 'required|min_length[5]|max_length[255]',
        'summary'     => 'required|min_length[10]',
        'description' => 'required|min_length[10]',
        'author_id'   => 'required|integer',
        'order_index' => 'required|integer'
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
}