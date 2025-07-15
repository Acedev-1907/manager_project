<?php

namespace App\Services;

use GuzzleHttp\Client;
use Exception;

class GoogleDriveService
{
    /**
     * Get access token from refresh token using Google OAuth2
     * @return string
     * @throws Exception
     */
    public function getAccessTokenFromRefreshToken()
    {
        $client = new Client();
        $response = $client->post('https://oauth2.googleapis.com/token', [
            'form_params' => [
                'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
                'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
                'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
                'grant_type' => 'refresh_token',
            ],
        ]);
        $data = json_decode($response->getBody(), true);
        if (empty($data['access_token'])) {
            throw new Exception('Failed to get access token from Google. Response: ' . json_encode($data));
        }
        return $data['access_token'];
    }

    /**
     * Create folder on Google Drive if not exists, return folder id
     */
    public function createFolderIfNotExists($name, $parentId, $accessToken, $client)
    {
        // Search for folder
        $query = sprintf("name='%s' and mimeType='application/vnd.google-apps.folder' and '%s' in parents and trashed=false", $name, $parentId);
        $res = $client->get('https://www.googleapis.com/drive/v3/files', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
            ],
            'query' => [
                'q' => $query,
                'fields' => 'files(id, name)',
            ]
        ]);
        $data = json_decode($res->getBody(), true);
        if (!empty($data['files'][0]['id'])) {
            return $data['files'][0]['id'];
        }
        // Create folder
        $res = $client->post('https://www.googleapis.com/drive/v3/files', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'name' => $name,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$parentId],
            ]),
        ]);
        $data = json_decode($res->getBody(), true);
        return $data['id'];
    }

    /**
     * Upload file to Google Drive and return public link
     * @param $file UploadedFile
     * @return array ['file_id' => string, 'link' => string]
     * @throws Exception
     */
    public function upload($file, $userId = null)
    {
        $accessToken = $this->getAccessTokenFromRefreshToken();
        $client = new Client();

        try {
            // Determine parent folder
            $rootFolderId = env('GOOGLE_DRIVE_FOLDER_ID', 'root');
            if ($userId) {
                $userFolderId = $this->createFolderIfNotExists($userId, $rootFolderId, $accessToken, $client);
                $avatarFolderId = $this->createFolderIfNotExists('avatar', $userFolderId, $accessToken, $client);
                $parentFolderId = $avatarFolderId;
            } else {
                $parentFolderId = $rootFolderId;
            }
            // Upload file
            $response = $client->request('POST', 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'multipart' => [
                    [
                        'name'     => 'metadata',
                        'contents' => json_encode([
                            'name' => method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : $file->getFilename(),
                            'parents' => [$parentFolderId]
                        ]),
                        'headers'  => [
                            'Content-Type' => 'application/json; charset=UTF-8'
                        ]
                    ],
                    [
                        'name'     => 'file',
                        'contents' => fopen($file->getRealPath(), 'r'),
                        'headers'  => [
                            'Content-Type' => $file->getMimeType()
                        ]
                    ]
                ]
            ]);
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);
            if (empty($data['id'])) {
                throw new Exception('Failed to upload file to Google Drive. Response: ' . $body);
            }

            // Set quyền public cho file
            $permResponse = $client->post("https://www.googleapis.com/drive/v3/files/{$data['id']}/permissions", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode([
                    'role' => 'reader',
                    'type' => 'anyone',
                ]),
            ]);
            $permBody = $permResponse->getBody()->getContents();
            \Log::info('Google Drive permission response', ['body' => $permBody]);

            $publicLink = 'https://drive.google.com/uc?export=view&id=' . $data['id'];
            return [
                'file_id' => $data['id'],
                'link' => $publicLink
            ];
        } catch (\Exception $e) {
            \Log::error('Google Drive upload error', ['error' => $e->getMessage()]);
            throw new Exception('Google Drive upload failed: ' . $e->getMessage());
        }
    }
}
