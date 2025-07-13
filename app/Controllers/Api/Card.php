<?php namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\CardModel;
use App\Models\CardImageModel;
use App\Models\AuthorModel;
use App\Models\ThemeModel;
use CodeIgniter\Files\File;

class Card extends ResourceController
{
    protected $modelName = 'App\Models\CardModel';
    protected $format    = 'json';

    public function index()
    {
        $cards = $this->model->findAll();
        $cardImageModel = new CardImageModel();
        $authorModel = new AuthorModel();
        $themeModel = new ThemeModel();

        foreach ($cards as &$card) {
            $card['images'] = $cardImageModel->where('card_id', $card['id'])->orderBy('order_index', 'ASC')->findAll();

            $author = $authorModel->find($card['author_id']);
            $card['authorName'] = $author ? $author['name'] : 'Nezināms autors';

            $theme = $themeModel->find($card['theme_id']);
            $card['themeName'] = $theme ? $theme['name'] : 'Nezināma tēma';
        }

        return $this->respond($cards);
    }

    public function show($id = null)
    {
        $card = $this->model->find($id);
        if ($card) {
            $cardImageModel = new CardImageModel();
            $authorModel = new AuthorModel();
            $themeModel = new ThemeModel();

            $card['images'] = $cardImageModel->where('card_id', $card['id'])->orderBy('order_index', 'ASC')->findAll();
            $author = $authorModel->find($card['author_id']);
            $card['authorName'] = $author ? $author['name'] : 'Nezināms autors';
            $theme = $themeModel->find($card['theme_id']);
            $card['themeName'] = $theme ? $theme['name'] : 'Nezināma tēma';

            return $this->respond($card);
        } else {
            return $this->failNotFound('Kartīte ar ID ' . $id . ' nav atrasta.');
        }
    }

    public function create()
    {
        // Frontend sūta FormData, tāpēc izmantojam getPost()
        $input = $this->request->getPost(); 

        $rules = [
            'theme'       => 'required|integer',
            'title'       => 'required|min_length[5]|max_length[255]',
            'summary'     => 'required|min_length[10]',
            'description' => 'required|min_length[10]',
            'authorId'    => 'required',
        ];

        // Pārbaudām failu validāciju, ja tie ir augšupielādēti
        $allRequestFiles = $this->request->getFiles();
        if (isset($input['images']) && is_array($input['images'])) {
            foreach ($input['images'] as $index => $imageData) {
                $uploadedFile = $allRequestFiles->getFile('images['.$index.'][file]');
                if ($uploadedFile && $uploadedFile->isValid()) {
                    $rules['images['.$index.'][file]'] = 'uploaded[images['.$index.'][file]]|max_size[images['.$index.'][file],10000]|ext_in[images['.$index.'][file],jpg,jpeg,png,gif]'; // Max 10MB
                }
            }
        }

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $authorModel = new AuthorModel();
        $cardAuthorId = $input['authorId'];
        if (strpos($cardAuthorId, 'new-author-') === 0) {
            $newAuthorName = substr($cardAuthorId, strlen('new-author-'));
            $authorData = ['name' => $newAuthorName];
            if ($authorModel->insert($authorData)) {
                $cardAuthorId = $authorModel->insertID();
            } else {
                return $this->failServerError('Neizdevās izveidot jaunu autoru kartītei.');
            }
        }

        $cardData = [
            'theme_id'    => (int)$input['theme'],
            'title'       => $input['title'],
            'summary'     => $input['summary'],
            'description' => $input['description'],
            'author_id'   => (int)$cardAuthorId
        ];

        $this->model->transBegin();

        try {
            if (!$this->model->insert($cardData)) {
                throw new \Exception('Neizdevās izveidot kartīti.');
            }
            $newCardId = $this->model->insertID();

            $cardImageModel = new CardImageModel();
            $imagesData = $input['images'] ?? [];

            foreach ($imagesData as $index => $imageData) {
                $imageUrl = '';
                $imageAuthorId = $imageData['authorId'] ?? null;

                if ($imageAuthorId && strpos($imageAuthorId, 'new-author-') === 0) {
                    $newImageAuthorName = substr($imageAuthorId, strlen('new-author-'));
                    $authorData = ['name' => $newImageAuthorName];
                    if ($authorModel->insert($authorData)) {
                        $imageAuthorId = $authorModel->insertID();
                    } else {
                        throw new \Exception("Neizdevās izveidot jaunu autoru attēlam {$index}.");
                    }
                }

                $uploadedFile = $allRequestFiles->getFile('images['.$index.'][file]');

                if ($uploadedFile && $uploadedFile->isValid() && !$uploadedFile->hasMoved()) {
                    $imageUrl = upload_file_with_structure(
                        $uploadedFile,
                        'images',
                        (int)$cardData['theme_id'],
                        (int)$newCardId,
                        $cardData['title'] . '_' . str_pad((string)$index, 2, '0', STR_PAD_LEFT)
                    );

                    if (!$imageUrl) {
                        throw new \Exception("Attēla faila augšupielāde {$index} neizdevās.");
                    }
                } elseif (isset($imageData['url']) && !empty($imageData['url'])) {
                    $imageUrl = $imageData['url'];
                } else {
                    if (empty($imageData['description']) && empty($imageAuthorId)) {
                        continue;
                    }
                    throw new \Exception("Attēla {$index} apraksts vai autors norādīts, bet nav attēla faila vai URL.");
                }

                if (empty($imageUrl)) {
                    throw new \Exception("Attēla {$index} URL ir tukšs.");
                }
                if (empty($imageData['description'])) {
                    throw new \Exception("Attēla {$index} apraksts ir obligāts.");
                }
                if (empty($imageAuthorId)) {
                    throw new \Exception("Attēla {$index} autors ir obligāts.");
                }

                $cardImageModel->insert([
                    'card_id'     => $newCardId,
                    'url'         => $imageUrl,
                    'description' => $imageData['description'],
                    'author_id'   => (int)$imageAuthorId,
                    'order_index' => (int)$index
                ]);
            }

            $this->model->transCommit();
            $newCard = $this->model->find($newCardId);
            return $this->respondCreated($newCard, 'Kartīte veiksmīgi izveidota.');

        } catch (\Exception $e) {
            $this->model->transRollback();
            $cardImagesPath = UPLOAD_PATH . 'images' . DIRECTORY_SEPARATOR . (int)$cardData['theme_id'] . DIRECTORY_SEPARATOR . (int)$newCardId . DIRECTORY_SEPARATOR;
            if (is_dir($cardImagesPath)) {
                delete_directory_recursive($cardImagesPath);
            }
            return $this->failServerError('Neizdevās izveidot kartīti: ' . $e->getMessage());
        }
    }

    public function update($id = null)
    {
        // LABOTS: getPost() atgriež POST datus kā masīvu (strādā ar FormData)
        // Frontend (api/index.js) sūta FormData priekš updateCard.
        $input = $this->request->getPost();
        $authorModel = new AuthorModel();

        $cardAuthorId = $input['authorId'];
        if (strpos($cardAuthorId, 'new-author-') === 0) {
            $newAuthorName = substr($cardAuthorId, strlen('new-author-'));
            $authorData = ['name' => $newAuthorName];
            if ($authorModel->insert($authorData)) {
                $cardAuthorId = $authorModel->insertID();
            } else {
                return $this->failServerError('Neizdevās izveidot jaunu autoru kartītei.');
            }
        }

        $cardData = [
            'theme_id'    => (int)$input['theme'],
            'title'       => $input['title'],
            'summary'     => $input['summary'],
            'description' => $input['description'],
            'author_id'   => (int)$cardAuthorId
        ];

        $this->model->transBegin();

        try {
            if (!$this->model->update($id, $cardData)) {
                throw new \Exception('Neizdevās atjaunināt kartītes pamatdatus.');
            }

            $cardImageModel = new CardImageModel();

            $oldImages = $cardImageModel->where('card_id', $id)->findAll();
            foreach ($oldImages as $oldImage) {
                delete_file_by_url($oldImage['url']);
                $cardImageModel->delete($oldImage['id']);
            }
            $cardImagesPath = UPLOAD_PATH . 'images' . DIRECTORY_SEPARATOR . (int)$cardData['theme_id'] . DIRECTORY_SEPARATOR . (int)$id . DIRECTORY_SEPARATOR;
            if (is_dir($cardImagesPath)) {
                delete_directory_recursive($cardImagesPath);
            }

            $allRequestFiles = $this->request->getFiles();
            $imagesData = $input['images'] ?? [];

            foreach ($imagesData as $index => $imageData) {
                $imageUrl = '';
                $imageAuthorId = $imageData['authorId'] ?? null;

                if ($imageAuthorId && strpos($imageAuthorId, 'new-author-') === 0) {
                    $newImageAuthorName = substr($imageAuthorId, strlen('new-author-'));
                    $authorData = ['name' => $newImageAuthorName];
                    if ($authorModel->insert($authorData)) {
                        $imageAuthorId = $authorModel->insertID();
                    } else {
                        throw new \Exception("Neizdevās izveidot jaunu autoru attēlam {$index}.");
                    }
                }

                $uploadedFile = $allRequestFiles->getFile('images['.$index.'][file]');

                if ($uploadedFile && $uploadedFile->isValid() && !$uploadedFile->hasMoved()) {
                    $imageUrl = upload_file_with_structure(
                        $uploadedFile,
                        'images',
                        (int)$cardData['theme_id'],
                        (int)$id,
                        $cardData['title'] . '_' . str_pad((string)$index, 2, '0', STR_PAD_LEFT)
                    );
                    if (!$imageUrl) {
                        throw new \Exception("Attēla faila augšupielāde {$index} neizdevās.");
                    }
                } elseif (isset($imageData['url']) && !empty($imageData['url'])) {
                    $imageUrl = $imageData['url'];
                } else {
                    if (empty($imageData['description']) && empty($imageAuthorId)) {
                        continue;
                    }
                    throw new \Exception("Attēla {$index} apraksts vai autors norādīts, bet nav attēla faila vai URL.");
                }

                if (empty($imageUrl)) {
                    throw new \Exception("Attēla {$index} URL ir tukšs.");
                }
                if (empty($imageData['description'])) {
                    throw new \Exception("Attēla {$index} apraksts ir obligāts.");
                }
                if (empty($imageAuthorId)) {
                    throw new \Exception("Attēla {$index} autors ir obligāts.");
                }

                $cardImageModel->insert([
                    'card_id'     => (int)$id,
                    'url'         => $imageUrl,
                    'description' => $imageData['description'],
                    'author_id'   => (int)$imageAuthorId,
                    'order_index' => (int)$index
                ]);
            }

            $this->model->transCommit();
            $updatedCard = $this->model->find($id);
            return $this->respond($updatedCard, 200, 'Kartīte veiksmīgi atjaunināta.');

        } catch (\Exception $e) {
            $this->model->transRollback();
            $cardImagesPath = UPLOAD_PATH . 'images' . DIRECTORY_SEPARATOR . (int)$cardData['theme_id'] . DIRECTORY_SEPARATOR . (int)$id . DIRECTORY_SEPARATOR;
            if (is_dir($cardImagesPath)) {
                delete_directory_recursive($cardImagesPath);
            }
            return $this->failServerError('Neizdevās atjaunināt kartīti: ' . $e->getMessage());
        }
    }

    public function delete($id = null)
    {
        $card = $this->model->find($id);
        if (!$card) {
            return $this->failNotFound('Kartīte ar ID ' . $id . ' nav atrasta.');
        }

        $cardImageModel = new CardImageModel();
        $oldImages = $cardImageModel->where('card_id', $id)->findAll();

        $this->model->transBegin();

        try {
            foreach ($oldImages as $oldImage) {
                delete_file_by_url($oldImage['url']);
                $cardImageModel->delete($oldImage['id']);
            }

            if (!$this->model->delete($id)) {
                throw new \Exception('Neizdevās dzēst kartīti no datubāzes.');
            }

            $this->model->transCommit();

            $cardFolderPath = UPLOAD_PATH . 'images' . DIRECTORY_SEPARATOR . $card['theme_id'] . DIRECTORY_SEPARATOR . $card['id'] . DIRECTORY_SEPARATOR;
            if (is_dir($cardFolderPath)) {
                delete_directory_recursive($cardFolderPath);
            }

            return $this->respondDeleted(['id' => $id], 'Kartīte veiksmīgi dzēsta.');

        } catch (\Exception $e) {
            $this->model->transRollback();
            return $this->failServerError('Neizdevās dzēst kartīti: ' . $e->getMessage());
        }
    }
}