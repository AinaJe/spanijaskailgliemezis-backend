<?php namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ThemeModel;

class Theme extends ResourceController
{
    protected $modelName = 'App\Models\ThemeModel';
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
            return $this->failNotFound('Tēma ar ID ' . $id . ' nav atrasta.');
        }
    }

    public function create()
    {
        // LABOTS: getJson(true) atgriež JSON datus kā masīvu.
        // Frontend (api/index.js) sūta JSON priekš createTheme.
        $data = $this->request->getJSON(true);

        if ($this->model->insert($data)) {
            $newThemeId = $this->model->insertID();
            $newTheme = $this->model->find($newThemeId);
            return $this->respondCreated($newTheme, 'Tēma veiksmīgi izveidota.');
        } else {
            return $this->failValidationErrors($this->model->errors());
        }
    }

    public function update($id = null)
    {
        // Jau vajadzētu būt getJson(true)
        $data = $this->request->getJSON(true);

        if ($this->model->update($id, $data)) {
            $updatedTheme = $this->model->find($id);
            return $this->respond($updatedTheme, 200, 'Tēma veiksmīgi atjaunināta.');
        } else {
            return $this->failValidationErrors($this->model->errors() ?: 'Neizdevās atjaunināt tēmu.');
        }
    }

    public function delete($id = null)
    {
        if ($this->model->delete($id)) {
            return $this->respondDeleted(['id' => $id], 'Tēma veiksmīgi dzēsta.');
        } else {
            return $this->failServerError('Neizdevās dzēst tēmu vai tēma nav atrasta.');
        }
    }
}