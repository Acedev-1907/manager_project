<?php

namespace App\Services;

use App\Repositories\PostRepository;
use App\Models\PostLike;
use App\Models\PostComment;
use Illuminate\Support\Facades\Auth;

class PostService
{
    protected $postRepository;

    public function __construct(PostRepository $postRepository)
    {
        $this->postRepository = $postRepository;
    }

    public function getAllPosts()
    {
        return $this->postRepository->getAllPostsWithUser();
    }

    public function getPostById($id)
    {
        return $this->postRepository->getPostById($id);
    }

    public function createPost(array $data)
    {
        $data['user_id'] = Auth::id();
        return $this->postRepository->create($data);
    }

    /**
     * Get user's images from their posts
     */
    public function getUserImages($userId)
    {
        return $this->postRepository->getUserImages($userId);
    }

    /**
     * Toggle reaction on a post.
     * - Nếu chưa có -> tạo mới với type.
     * - Nếu đã có cùng type -> xoá (bỏ thích).
     * - Nếu đã có khác type -> cập nhật sang type mới.
     */
    public function toggleLike($postId, string $type = 'like')
    {
        $userId = Auth::id();
        $like = PostLike::where('post_id', $postId)
            ->where('user_id', $userId)
            ->first();

        if ($like) {
            if ($like->type === $type) {
                // cùng trạng thái -> bỏ thích
                $like->delete();
                return ['liked' => false, 'type' => null];
            }

            // khác trạng thái -> đổi sang type mới
            $like->type = $type;
            $like->save();
            return ['liked' => true, 'type' => $type];
        }

        PostLike::create([
            'post_id' => $postId,
            'user_id' => $userId,
            'type' => $type,
        ]);

        return ['liked' => true, 'type' => $type];
    }

    /**
     * Add comment to a post
     */
    public function addComment($postId, $content)
    {
        return PostComment::create([
            'post_id' => $postId,
            'user_id' => Auth::id(),
            'content' => $content,
        ])->load('user:id,name,avatar');
    }

    /**
     * Tăng số lần chia sẻ bài viết
     */
    public function sharePost($postId)
    {
        $post = $this->postRepository->getPostById($postId);
        $post->share_count = ($post->share_count ?? 0) + 1;
        $post->save();

        return $post->share_count;
    }
}



