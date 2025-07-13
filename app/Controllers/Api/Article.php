<?php namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ArticleModel;
use App\Models\AuthorModel;

class Article extends ResourceController
{
    protected $modelName = 'App\Models\ArticleModel';
    protected $format    = 'json';

    public function index()
    {
        $articles = $this->model->findAll();
        $authorModel = new AuthorModel();

        foreach ($articles as &$article) {
            $author = $authorModel->find($article['author_id']);
            $article['authorName'] = $author ? $author['name'] : 'Nezināms autors';
        }
        return $this->respond($articles);
    }

    public function show($id = null)
    {
        $data = $this->model->find($id);
        if ($data) {
            $authorModel = new AuthorModel();
            $author = $authorModel->find($data['author_id']);
            $data['authorName'] = $author ? $author['name'] : 'Nezināms autors';
            return $this->respond($data);
        } else {
            return $this->failNotFound('Raksts ar ID ' . $id . ' nav atrasts.');
        }
    }

    public function create()
    {
        // LABOTS: getJson(true) atgriež JSON datus kā masīvu.
        // Frontend (api/index.js) sūta JSON priekš createArticle.
        $input = $this->request->getJSON(true);
        $authorModel = new AuthorModel();

        $authorId = $input['authorId'];
        if (strpos($authorId, 'new-author-') === 0) {
            $newAuthorName = substr($authorId, strlen('new-author-'));
            $authorData = ['name' => $newAuthorName];
            if ($authorModel->insert($authorData)) {
                $authorId = $authorModel->insertID();
            } else {
                return $this->failServerError('Neizdevās izveidot jaunu autoru rakstam.');
            }
        }
        $input['authorId'] = $authorId;

        $data = [
            'date'      => $input['date'],
            'title'     => $input['title'],
            'summary'   => $input['summary'] ?? null,
            'link'      => $input['link'] ?? null,
            'author_id' => (int)$input['authorId']
        ];

        if ($this->model->insert($data)) {
            $newArticleId = $this->model->insertID();
            $newArticle = $this->model->find($newArticleId);
            return $this->respondCreated($newArticle, 'Raksts veiksmīgi izveidots.');
        } else {
            return $this->failValidationErrors($this->model->errors());
        }
    }

    public function update($id = null)
    {
        // Jau vajadzētu būt getJson(true)
        $input = $this->request->getJSON(true);
        $authorModel = new AuthorModel();

        $authorId = $input['authorId'];
        if (strpos($authorId, 'new-author-') === 0) {
            $newAuthorName = substr($authorId, strlen('new-author-'));
            $authorData = ['name' => $newAuthorName];
            if ($authorModel->insert($authorData)) {
                $authorId = $authorModel->insertID();
            } else {
                return $this->failServerError('Neizdevās izveidot jaunu autoru rakstam.');
            }
        }
        $input['authorId'] = $authorId;

        $data = [
            'date'      => $input['date'],
            'title'     => $input['title'],
            'summary'   => $input['summary'] ?? null,
            'link'      => $input['link'] ?? null,
            'author_id' => (int)$input['authorId']
        ];

        if ($this->model->update($id, $data)) {
            $updatedArticle = $this->model->find($id);
            return $this->respond($updatedArticle, 200, 'Raksts veiksmīgi atjaunināts.');
        } else {
            return $this->failValidationErrors($this->model->errors() ?: 'Neizdevās atjaunināt rakstu.');
        }
    }

    public function delete($id = null)
    {
        if ($this->model->delete($id)) {
            return $this->respondDeleted(['id' => $id], 'Raksts veiksmīgi dzēsts.');
        } else {
            return $this->failServerError('Neizdevās dzēst rakstu vai raksts nav atrasts.');
        }
    }
}