<?php

namespace App\Http\Controllers;

use App\Services\PostService;
use App\Services\ImageKitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class PostController extends Controller
{
    protected $postService;
    protected $imageKitService;

    public function __construct(PostService $postService, ImageKitService $imageKitService)
    {
        $this->postService = $postService;
        $this->imageKitService = $imageKitService;
    }

    public function index()
    {
        $posts = $this->postService->getAllPosts();
        return Response::json($posts);
    }

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

            $post = $this->postService->createPost($validated);

            return Response::json([
                'message' => 'Bài viết đã được đăng thành công!',
                'post' => $post->load('user:id,name,avatar'),
            ], 201);
        } catch (\Exception $e) {
            // Xử lý lỗi spam hoặc các lỗi khác
            return Response::json([
                'error' => $e->getMessage(),
            ], 429); // 429 Too Many Requests
        }
    }

    /**
     * Get user's image library (from previous posts with images)
     */
    public function getUserImages(Request $request)
    {
        $user = $request->user();
        $images = $this->postService->getUserImages($user->id);
        return Response::json($images);
    }

    /**
     * Upload images for post (separate endpoint) - supports multiple files
     */
    public function uploadImage(Request $request, ImageKitService $imageKit)
    {
        try {
            $user = $request->user();
            $files = $request->file('images');
            
            if (!$files || (is_array($files) && count($files) === 0)) {
                return Response::json(['error' => 'No files provided'], 400);
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
                return Response::json(['error' => 'No valid images uploaded'], 422);
            }

            return Response::json([
                'message' => 'Upload successful!',
                'images' => $uploadedImages
            ]);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Get post by ID with likes and comments
     */
    public function show($id)
    {
        $post = $this->postService->getPostById($id);
        return Response::json($post);
    }

    /**
     * Toggle like/reaction on a post
     */
    public function toggleLike(Request $request, $id)
    {
        $type = $request->input('type', 'like');
        $result = $this->postService->toggleLike($id, $type);
        $post = $this->postService->getPostById($id);

        return Response::json([
            'liked' => $result['liked'],
            'type' => $result['type'],
            'likes_count' => $post->likes_count,
            'likes' => $post->likes
        ]);
    }

    /**
     * Add comment to a post
     */
    public function addComment(Request $request, $id)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment = $this->postService->addComment($id, $validated['content']);
        $post = $this->postService->getPostById($id);
        
        return Response::json([
            'message' => 'Bình luận đã được thêm!',
            'comment' => $comment,
            'comments_count' => $post->comments_count
        ], 201);
    }

    /**
     * Tăng share_count cho bài viết
     */
    public function share($id)
    {
        $shareCount = $this->postService->sharePost($id);

        return Response::json([
            'share_count' => $shareCount,
        ]);
    }
}
