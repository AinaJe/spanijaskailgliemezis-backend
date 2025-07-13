<?php namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Models\VideoModel;
use App\Models\AuthorModel;
use App\Models\ThemeModel;
use CodeIgniter\Files\File;

class Video extends ResourceController
{
    protected $modelName = 'App\Models\VideoModel';
    protected $format    = 'json';

    public function index()
    {
        $videos = $this->model->findAll();
        $authorModel = new AuthorModel();
        $themeModel = new ThemeModel();

        foreach ($videos as &$video) {
            $author = $authorModel->find($video['author_id']);
            $video['authorName'] = $author ? $author['name'] : 'Nezināms autors';
            $theme = $themeModel->find(109);
            $video['themeName'] = $theme ? $theme['name'] : 'Video';
        }
        return $this->respond($videos);
    }

    public function show($id = null)
    {
        $data = $this->model->find($id);
        if ($data) {
            $authorModel = new AuthorModel();
            $themeModel = new ThemeModel();

            $author = $authorModel->find($data['author_id']);
            $data['authorName'] = $author ? $author['name'] : 'Nezināms autors';
            $theme = $themeModel->find(109);
            $data['themeName'] = $theme ? $theme['name'] : 'Video';

            return $this->respond($data);
        } else {
            return $this->failNotFound('Video ar ID ' . $id . ' nav atrasts.');
        }
    }

    public function create()
    {
        // LABOTS: getPost() atgriež POST datus kā masīvu. Tas strādā arī ar FormData.
        $input = $this->request->getPost(); 

        $rules = [
            'date'        => 'required|valid_date[Y-m-d]',
            'title'       => 'required|min_length[5]|max_length[255]',
            // required_without_file ir custom rule. Pārbaudīs, vai videoLink nav tukšs, ja nav videoFile.
            'videoLink'   => 'permit_empty|valid_url_strict|max_length[2048]', 
            'description' => 'required|min_length[10]',
            'authorId'    => 'required',
        ];

        $uploadedVideo = $this->request->getFile('videoFile');
        if ($uploadedVideo && $uploadedVideo->isValid() && !$uploadedVideo->hasMoved()) {
            $rules['videoFile'] = 'uploaded[videoFile]|max_size[videoFile,50000]|ext_in[videoFile,mp4,mov,avi,wmv,flv,webm]';
        } else {
            // Ja nav faila, tad videoLink ir obligāts
            $rules['videoLink'] = 'required|valid_url_strict|max_length[2048]';
        }

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $authorModel = new AuthorModel();
        $authorId = $input['authorId'];
        if (strpos($authorId, 'new-author-') === 0) {
            $newAuthorName = substr($authorId, strlen('new-author-'));
            $authorData = ['name' => $newAuthorName];
            if ($authorModel->insert($authorData)) {
                $authorId = $authorModel->insertID();
            } else {
                return $this->failServerError('Neizdevās izveidot jaunu autoru video.');
            }
        }
        $input['authorId'] = $authorId;

        $videoUrl = $input['videoLink'] ?? null;

        $this->model->transBegin();

        try {
            $videoData = [
                'date'        => $input['date'],
                'title'       => $input['title'],
                'summary'     => $input['summary'] ?? null,
                'description' => $input['description'],
                'author_id'   => (int)$input['authorId'],
                'video_link'  => $videoUrl
            ];

            if (!$this->model->insert($videoData)) {
                throw new \Exception('Neizdevās izveidot video datu ierakstu.');
            }
            $newVideoId = $this->model->insertID();

            if ($uploadedVideo && $uploadedVideo->isValid() && !$uploadedVideo->hasMoved()) {
                $videoThemeId = 109; // Video lapas tēmas ID
                $videoUrl = upload_file_with_structure(
                    $uploadedVideo,
                    'videos',
                    (int)$videoThemeId,
                    (int)$newVideoId,
                    $videoData['title']
                );

                if (!$videoUrl) {
                    throw new \Exception('Video faila augšupielāde neizdevās.');
                }

                if (!$this->model->update($newVideoId, ['video_link' => $videoUrl])) {
                    throw new \Exception('Neizdevās atjaunināt video URL datubāzē.');
                }
            }

            $this->model->transCommit();
            $newVideo = $this->model->find($newVideoId);
            return $this->respondCreated($newVideo, 'Video veiksmīgi izveidots.');

        } catch (\Exception $e) {
            $this->model->transRollback();
            $videoThemeId = 109;
            $videoFolderPath = UPLOAD_PATH . 'videos' . DIRECTORY_SEPARATOR . (int)$videoThemeId . DIRECTORY_SEPARATOR . (int)$newVideoId . DIRECTORY_SEPARATOR;
            if (is_dir($videoFolderPath)) {
                delete_directory_recursive($videoFolderPath);
            }
            return $this->failServerError('Neizdevās izveidot video: ' . $e->getMessage());
        }
    }

    public function update($id = null)
    {
        // LABOTS: getPost() atgriež datus kā masīvu (strādā arī ar FormData + _method PUT)
        $input = $this->request->getPost(); 
        $authorModel = new AuthorModel();

        $authorId = $input['authorId'];
        if (strpos($authorId, 'new-author-') === 0) {
            $newAuthorName = substr($authorId, strlen('new-author-'));
            $authorData = ['name' => $newAuthorName];
            if ($authorModel->insert($authorData)) {
                $authorId = $authorModel->insertID();
            } else {
                return $this->failServerError('Neizdevās izveidot jaunu autoru video.');
            }
        }
        $input['authorId'] = $authorId;

        $videoUrl = $input['videoLink'] ?? null;

        $rules = [
            'date'        => 'required|valid_date[Y-m-d]',
            'title'       => 'required|min_length[5]|max_length[255]',
            'description' => 'required|min_length[10]',
            'authorId'    => 'required',
        ];

        $uploadedVideo = $this->request->getFile('videoFile');
        if ($uploadedVideo && $uploadedVideo->isValid() && !$uploadedVideo->hasMoved()) {
            $rules['videoFile'] = 'uploaded[videoFile]|max_size[videoFile,50000]|ext_in[videoFile,mp4,mov,avi,wmv,flv,webm]';
        } else {
            $rules['videoLink'] = 'required|valid_url_strict|max_length[2048]';
        }

        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $videoData = [
            'date'        => $input['date'],
            'title'       => $input['title'],
            'summary'     => $input['summary'] ?? null,
            'description' => $input['description'],
            'author_id'   => (int)$input['authorId'],
            'video_link'  => $videoUrl
        ];

        $this->model->transBegin();

        try {
            $currentVideo = $this->model->find($id);
            if (!$currentVideo) {
                throw new \Exception('Video nav atrasts.');
            }

            if ($uploadedVideo && $uploadedVideo->isValid() && !$uploadedVideo->hasMoved()) {
                if (!empty($currentVideo['video_link'])) {
                    delete_file_by_url($currentVideo['video_link']);
                }

                $videoThemeId = 109;
                $videoUrl = upload_file_with_structure(
                    $uploadedVideo,
                    'videos',
                    (int)$videoThemeId,
                    (int)$id,
                    $videoData['title']
                );

                if (!$videoUrl) {
                    throw new \Exception('Video faila augšupielāde neizdevās atjaunināšanas laikā.');
                }
                $videoData['video_link'] = $videoUrl;
            }

            if (!$this->model->update($id, $videoData)) {
                throw new \Exception('Neizdevās atjaunināt video datu ierakstu.');
            }

            $this->model->transCommit();
            $updatedVideo = $this->model->find($id);
            return $this->respond($updatedVideo, 200, 'Video veiksmīgi atjaunināts.');

        } catch (\Exception $e) {
            $this->model->transRollback();
            $videoThemeId = 109;
            $videoFolderPath = UPLOAD_PATH . 'videos' . DIRECTORY_SEPARATOR . (int)$videoThemeId . DIRECTORY_SEPARATOR . (int)$id . DIRECTORY_SEPARATOR;
            if (is_dir($videoFolderPath)) {
                delete_directory_recursive($videoFolderPath);
            }
            return $this->failServerError('Neizdevās atjaunināt video: ' . $e->getMessage());
        }
    }

    public function delete($id = null)
    {
        $video = $this->model->find($id);
        if (!$video) {
            return $this->failNotFound('Video ar ID ' . $id . ' nav atrasts.');
        }

        $this->model->transBegin();

        try {
            if (!empty($video['video_link'])) {
                delete_file_by_url($video['video_link']);
            }

            if (!$this->model->delete($id)) {
                throw new \Exception('Neizdevās dzēst video no datubāzes.');
            }

            $this->model->transCommit();

            $videoThemeId = 109;
            $videoFolderPath = UPLOAD_PATH . 'videos' . DIRECTORY_SEPARATOR . (int)$videoThemeId . DIRECTORY_SEPARATOR . (int)$id . DIRECTORY_SEPARATOR;
            if (is_dir($videoFolderPath)) {
                delete_directory_recursive($videoFolderPath);
            }

            return $this->respondDeleted(['id' => $id], 'Video veiksmīgi dzēsts.');

        } catch (\Exception $e) {
            $this->model->transRollback();
            return $this->failServerError('Neizdevās dzēst video: ' . $e->getMessage());
        }
    }
}