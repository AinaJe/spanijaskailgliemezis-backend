<?php namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\CardImageModel;
use App\Models\AuthorModel; // Autora modelis

class CardImage extends ResourceController
{
    protected $modelName = 'App\Models\CardImageModel';
    protected $format    = 'json';

    /**
     * Iegūst visus attēlus. Varbūt noder, bet parasti tos ielādē caur Card kontrolieri.
     * Atbilst GET /api/card_images
     */
    public function index()
    {
        $cardImages = $this->model->findAll();
        $authorModel = new AuthorModel();

        foreach ($cardImages as &$image) {
            $author = $authorModel->find($image['author_id']);
            $image['authorName'] = $author ? $author['name'] : 'Nezināms autors';
        }

        return $this->respond($cardImages);
    }

    /**
     * Iegūst vienu attēlu.
     * Atbilst GET /api/card_images/{id}
     */
    public function show($id = null)
    {
        $data = $this->model->find($id);
        if ($data) {
            $authorModel = new AuthorModel();
            $author = $authorModel->find($data['author_id']);
            $data['authorName'] = $author ? $author['name'] : 'Nezināms autors';
            return $this->respond($data);
        } else {
            return $this->failNotFound('Attēls ar ID ' . $id . ' nav atrasts.');
        }
    }

    /**
     * Izveido jaunu attēlu.
     * Atbilst POST /api/card_images
     * Šī metode parasti tiek izsaukta no Card kontroliera, nevis tieši no frontenda.
     * Ja frontend sūta failu, šeit jāapstrādā failu augšupielāde.
     */
    public function create()
    {
        // Frontend var sūtīt FormData ar failiem vai JSON ar URL
        $data = $this->request->getVar();

        $authorModel = new AuthorModel();
        $authorId = $data['author_id'];

        if (strpos($authorId, 'new-author-') === 0) {
            $newAuthorName = substr($authorId, strlen('new-author-'));
            $authorData = ['name' => $newAuthorName];
            if ($authorModel->insert($authorData)) {
                $authorId = $authorModel->insertID();
            } else {
                return $this->failValidationErrors(['author_id' => 'Neizdevās izveidot jaunu autoru attēlam.']);
            }
        }
        $data['author_id'] = $authorId;

        // Ja ir failu augšupielāde
        $imageUrl = $data['url'] ?? null;
        if ($this->request->getFile('image_file') && $this->request->getFile('image_file')->isValid() && !$this->request->getFile('image_file')->hasMoved()) {
            $file = $this->request->getFile('image_file');
            $newName = $file->getRandomName();
            $file->move(WRITEPATH . 'uploads/images', $newName); // Saglabā failu publiski pieejamā vietā
            $imageUrl = base_url('uploads/images/' . $newName); // Izveido URL
        }

        $imageData = [
            'card_id'     => $data['card_id'],
            'url'         => $imageUrl,
            'description' => $data['description'],
            'author_id'   => $data['author_id'],
            'order_index' => $data['order_index'] ?? 0
        ];

        if ($this->model->insert($imageData)) {
            $newImageId = $this->model->insertID();
            $newImage = $this->model->find($newImageId);
            return $this->respondCreated($newImage, 'Attēls veiksmīgi izveidots.');
        } else {
            return $this->failValidationErrors($this->model->errors());
        }
    }

    /**
     * Atjaunina esošu attēlu.
     * Atbilst PUT /api/card_images/{id}
     */
    public function update($id = null)
    {
        $input = $this->request->getRawInput();
        $authorModel = new AuthorModel();
        $authorId = $input['author_id'];

        if (strpos($authorId, 'new-author-') === 0) {
            $newAuthorName = substr($authorId, strlen('new-author-'));
            $authorData = ['name' => $newAuthorName];
            if ($authorModel->insert($authorData)) {
                $authorId = $authorModel->insertID();
            } else {
                return $this->failValidationErrors(['author_id' => 'Neizdevās izveidot jaunu autoru attēlam.']);
            }
        }
        $input['author_id'] = $authorId;

        // Ja ir failu augšupielāde PUT pieprasījumā, tas ir sarežģītāk ar getRawInput().
        // Varbūt labāk izmantot atsevišķu PATCH/POST endpunktu tikai failu augšupielādei.
        // Pašlaik pieņemam, ka URL tiek sūtīts tieši.
        $data = [
            'url'         => $input['url'],
            'description' => $input['description'],
            'author_id'   => $input['author_id'],
            'order_index' => $input['order_index'] ?? 0
        ];

        if ($this->model->update($id, $data)) {
            $updatedImage = $this->model->find($id);
            return $this->respond($updatedImage, 200, 'Attēls veiksmīgi atjaunināts.');
        } else {
            return $this->failValidationErrors($this->model->errors() ?: 'Neizdevās atjaunināt attēlu.');
        }
    }

    /**
     * Dzēš attēlu.
     * Atbilst DELETE /api/card_images/{id}
     */
    public function delete($id = null)
    {
        if ($this->model->delete($id)) {
            return $this->respondDeleted(['id' => $id], 'Attēls veiksmīgi dzēsts.');
        } else {
            return $this->failServerError('Neizdevās dzēst attēlu vai attēls nav atrasts.');
        }
    }
}