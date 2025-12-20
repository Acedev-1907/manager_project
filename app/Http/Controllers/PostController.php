<?php

namespace App\Http\Controllers;

use App\Services\PostService;
use App\Services\ImageKitService;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;

/**
 * Post Controller
 * 
 * Handles all post-related operations including creating posts,
 * uploading images, managing likes, comments, and shares.
 */
class PostController extends ApiController
{
    protected $postService;
    protected $imageKitService;

    public function __construct(PostService $postService, ImageKitService $imageKitService)
    {
        $this->postService = $postService;
        $this->imageKitService = $imageKitService;
    }

    /**
     * Get all posts
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $posts = $this->postService->getAllPosts();
        return $this->respondWithData($posts, 'Posts retrieved successfully');
    }

    /**
     * Create a new post
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'images' => 'nullable|array',
            'images.*' => 'string|url',
        ]);

        try {
            // Handle multiple image uploads if files are provided
            if ($request->hasFile('images')) {
                $files = $request->file('images');
                $user = $request->user();
                $folder = env('IMAGEKIT_AVATAR_FOLDER', 'app-manager-project') . '/posts/' . $user->id;
                $uploadedImages = [];

                foreach ($files as $file) {
                    $result = $this->imageKitService->upload($file, $folder);
                    $uploadedImages[] = $result['url'];
                }

                $validated['images'] = $uploadedImages;
            }

            $post = $this->postService->createPost($validated, $request);

            return $this->setStatusCode(201)
                ->setReturnCode(self::RESPONSE_CREATED)
                ->respondWithData([
                    'post' => $post->load('user:id,name,avatar')
                ], 'Post created successfully');
        } catch (\Exception $e) {
            // Handle spam or other errors
            return $this->setStatusCode(429)
                ->setReturnCode(self::ERROR_VALIDATION)
                ->respondWithError($e->getMessage());
        }
    }

    /**
     * Get user's image library (from previous posts with images)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserImages(Request $request)
    {
        $user = $request->user();
        $images = $this->postService->getUserImages($user->id);
        return $this->respondWithData($images, 'User images retrieved successfully');
    }

    /**
     * Upload images for post (separate endpoint) - supports multiple files
     * 
     * @param Request $request
     * @param ImageKitService $imageKit
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage(Request $request, ImageKitService $imageKit)
    {
        try {
            $user = $request->user();
            $files = $request->file('images');
            
            if (!$files || (is_array($files) && count($files) === 0)) {
                return $this->setStatusCode(400)
                    ->setReturnCode(self::ERROR_VALIDATION)
                    ->respondWithError('No files provided');
            }

            // Ensure files is an array
            if (!is_array($files)) {
                $files = [$files];
            }

            $folder = env('IMAGEKIT_AVATAR_FOLDER', 'app-manager-project') . '/posts/' . $user->id;
            $uploadedImages = [];

            foreach ($files as $file) {
                // Validate each file
                $validator = \Illuminate\Support\Facades\Validator::make(['image' => $file], [
                    'image' => 'required|file|mimes:jpg,jpeg,png,gif,webp|max:10240' // 10MB max
                ]);

                if ($validator->fails()) {
                    continue; // Skip invalid files
                }

                $result = $imageKit->upload($file, $folder);
                $uploadedImages[] = [
                    'url' => $result['url'],
                    'file_id' => $result['file_id'],
                    'thumbnail' => $result['thumbnail']
                ];
            }

            if (count($uploadedImages) === 0) {
                return $this->respondValidationError('No valid images uploaded');
            }

            return $this->respondWithData([
                'images' => $uploadedImages
            ], 'Upload successful');
        } catch (\Exception $e) {
            return $this->respondValidationError($e->getMessage());
        }
    }

    /**
     * Get post by ID with likes and comments
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $post = $this->postService->getPostById($id);
        if (!$post) {
            return $this->respondNotFound('Post not found');
        }
        return $this->respondWithData($post, 'Post retrieved successfully');
    }

    /**
     * Toggle like/reaction on a post
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleLike(Request $request, $id)
    {
        $type = $request->input('type', 'like');
        $result = $this->postService->toggleLike($id, $type);
        $post = $this->postService->getPostById($id);

        return $this->respondWithData([
            'liked' => $result['liked'],
            'type' => $result['type'],
            'likes_count' => $post->likes_count,
            'likes' => $post->likes
        ], 'Like toggled successfully');
    }

    /**
     * Add comment to a post
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addComment(Request $request, $id)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment = $this->postService->addComment($id, $validated['content'], $request);
        $post = $this->postService->getPostById($id);
        
        return $this->setStatusCode(201)
            ->setReturnCode(self::RESPONSE_CREATED)
            ->respondWithData([
                'comment' => $comment,
                'comments_count' => $post->comments_count
            ], 'Comment added successfully');
    }

    /**
     * Share a post (increment share count)
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function share($id)
    {
        $shareCount = $this->postService->sharePost($id);

        return $this->respondWithData([
            'share_count' => $shareCount,
        ], 'Post shared successfully');
    }
}
