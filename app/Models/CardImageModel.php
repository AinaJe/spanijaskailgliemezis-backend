<?php namespace App\Models;

use CodeIgniter\Model;

class CardImageModel extends Model
{
    protected $table = 'card_images';
    protected $primaryKey = 'id';
    protected $allowedFields = ['card_id', 'url', 'description', 'author_id', 'order_index'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $returnType    = 'array';

    protected $validationRules = [
        'card_id'     => 'required|integer',
        'url'         => 'required|valid_url|max_length[2048]',
        'description' => 'required|min_length[5]',
        'author_id'   => 'required|integer',
        'order_index' => 'required|integer'
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
}