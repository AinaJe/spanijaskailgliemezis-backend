<?php namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\AuthorModel;

class Author extends ResourceController
{
    protected $modelName = 'App\Models\AuthorModel';
    protected $format    = 'json';

    public function index()
    {
        return $this->respond($this->model->findAll());
    }

    public function show($id = null)
    {
        $data = $this->model->find($id);
        if ($data) {
            return $this->respond($data);
        } else {
            return $this->failNotFound('Autors ar ID ' . $id . ' nav atrasts.');
        }
    }

    public function create()
    {
        // LABOTS: getJson(true) atgriež JSON datus kā masīvu.
        // Frontend (api/index.js) sūta JSON priekš createAuthor.
        $input = $this->request->getJSON(true);

        $rules = [
            'name' => 'required|min_length[3]|max_length[255]'
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $name = $input['name'];
        if (strpos($name, 'new-author-') === 0) {
            $name = substr($name, strlen('new-author-'));
        }
        $data = ['name' => $name];

        if ($this->model->insert($data)) {
            $newAuthorId = $this->model->insertID();
            $newAuthor = $this->model->find($newAuthorId);
            return $this->respondCreated($newAuthor, 'Autors veiksmīgi izveidots.');
        } else {
            return $this->failValidationErrors($this->model->errors());
        }
    }

    public function update($id = null)
    {
        // LABOTS: getJson(true) atgriež JSON datus kā masīvu.
        // Frontend (api/index.js) sūta JSON priekš updateAuthor.
        $input = $this->request->getJSON(true);

        $rules = [
            'name' => 'required|min_length[3]|max_length[255]'
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $data = [
            'name' => $input['name'] ?? null
        ];

        if ($this->model->update($id, $data)) {
            $updatedAuthor = $this->model->find($id);
            return $this->respond($updatedAuthor, 200, 'Autors veiksmīgi atjaunināts.');
        } else {
            return $this->failValidationErrors($this->model->errors() ?: 'Neizdevās atjaunināt autoru.');
        }
    }

    public function delete($id = null)
    {
        if ($this->model->delete($id)) {
            return $this->respondDeleted(['id' => $id], 'Autors veiksmīgi dzēsts.');
        } else {
            return $this->failServerError('Neizdevās dzēst autoru vai autors nav atrasts.');
        }
    }
}