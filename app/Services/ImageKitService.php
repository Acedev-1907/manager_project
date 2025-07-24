<?php

namespace App\Services;

use ImageKit\ImageKit;

class ImageKitService
{
    protected $imageKit;

    public function __construct()
    {
        $this->imageKit = new ImageKit(
            env('IMAGEKIT_PUBLIC_KEY'),
            env('IMAGEKIT_PRIVATE_KEY'),
            env('IMAGEKIT_URL_ENDPOINT')
        );
    }

    /**
     * Upload file to ImageKit
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $folder
     * @return array
     */
    public function upload($file, $folder = null)
    {
        $fileData = fopen($file->getRealPath(), 'r');
        $fileName = $file->getClientOriginalName();
        $folder = $folder ?? env('IMAGEKIT_AVATAR_FOLDER', '/app-manager-project/avatars/');

        $upload = $this->imageKit->upload([
            'file' => $fileData,
            'fileName' => $fileName,
            'folder' => $folder,
        ]);

        if (isset($upload->result) && isset($upload->result->url)) {
            return [
                'file_id' => $upload->result->fileId,
                'url' => $upload->result->url,
                'thumbnail' => $upload->result->thumbnailUrl ?? null,
            ];
        } else {
            throw new \Exception('ImageKit upload failed: ' . json_encode($upload->error ?? []));
        }
    }
}
