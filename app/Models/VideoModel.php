<?php namespace App\Models;

use CodeIgniter\Model;

class VideoModel extends Model
{
    protected $table = 'videos';
    protected $primaryKey = 'id';
    protected $allowedFields = ['date', 'title', 'summary', 'video_link', 'description', 'author_id'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $returnType    = 'array';

    protected $validationRules = [
        'date'        => 'required|valid_date[Y-m-d]',
        'title'       => 'required|min_length[5]|max_length[255]',
        'summary'     => 'permit_empty|max_length[65535]',
        'video_link'  => 'required|valid_url|max_length[2048]', // video_link ir obligāta, jo jūsu frontend to pieprasa
        'description' => 'required|min_length[10]',
        'author_id'   => 'required|integer'
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
}