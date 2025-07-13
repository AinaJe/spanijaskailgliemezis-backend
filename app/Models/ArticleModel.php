<?php namespace App\Models;

use CodeIgniter\Model;

class ArticleModel extends Model
{
    protected $table = 'articles';
    protected $primaryKey = 'id';
    protected $allowedFields = ['date', 'title', 'summary', 'link', 'author_id'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $returnType    = 'array';

    protected $validationRules = [
        'date'        => 'required|valid_date[Y-m-d]',
        'title'       => 'required|min_length[5]|max_length[255]',
        'summary'     => 'permit_empty|max_length[65535]', // summary var būt tukšs
        'link'        => 'permit_empty|valid_url|max_length[2048]', // link var būt tukšs
        'author_id'   => 'required|integer'
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
}