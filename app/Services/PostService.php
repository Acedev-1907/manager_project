<?php

namespace App\Services;

use App\Repositories\PostRepository;
use App\Models\PostLike;
use App\Models\PostComment;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

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

    /**
     * Kiểm tra spam tạo bài post
     * Nếu user tạo quá 5 bài liên tục (trong khoảng thời gian ngắn) thì block
     */
    protected function checkSpam($userId)
    {
        $blockKey = "post_spam_block_{$userId}";
        
        // Kiểm tra xem user có đang bị block không
        if (Cache::has($blockKey)) {
            $blockUntil = Cache::get("post_spam_block_until_{$userId}");
            if ($blockUntil) {
                $remainingMinutes = max(0, Carbon::parse($blockUntil)->diffInMinutes(Carbon::now()));
                throw new \Exception("Bạn đã bị tạm khóa do tạo quá nhiều bài viết liên tục. Vui lòng đợi {$remainingMinutes} phút trước khi đăng bài tiếp theo.");
            }
            throw new \Exception('Bạn đã bị tạm khóa do tạo quá nhiều bài viết liên tục. Vui lòng đợi một chút trước khi đăng bài tiếp theo.');
        }
        
        $maxPosts = 4; // Kiểm tra 4 bài gần nhất (bài hiện tại sẽ là bài thứ 5)
        $timeWindow = 3; // 3 phút - khoảng thời gian để coi là "liên tục"
        
        // Lấy 4 bài post gần nhất của user (bài hiện tại sẽ là bài thứ 5)
        $recentPosts = Post::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($maxPosts)
            ->get(['created_at']);
        
        // Nếu có đủ 4 bài post gần nhất
        if ($recentPosts->count() >= $maxPosts) {
            $now = Carbon::now();
            $oldestPostTime = Carbon::parse($recentPosts->last()->created_at);
            
            // Kiểm tra xem khoảng thời gian giữa bài cũ nhất và hiện tại có nhỏ hơn timeWindow không
            $timeSpan = $now->diffInMinutes($oldestPostTime);
            
            // Nếu 4 bài được tạo trong vòng 3 phút -> đây sẽ là bài thứ 5 -> spam
            if ($timeSpan <= $timeWindow) {
                // Lưu vào cache để block user trong một khoảng thời gian
                $blockDuration = 30; // Block 30 phút
                $blockUntilTime = now()->addMinutes($blockDuration);
                Cache::put($blockKey, true, $blockUntilTime);
                Cache::put("post_spam_block_until_{$userId}", $blockUntilTime->toDateTimeString(), $blockUntilTime);
                
                throw new \Exception('Bạn đã tạo quá nhiều bài viết liên tục (5 bài trong vòng 3 phút). Tài khoản của bạn đã bị tạm khóa trong 30 phút. Vui lòng đợi trước khi đăng bài tiếp theo.');
            }
        }
        
        return true;
    }

    public function createPost(array $data)
    {
        $userId = Auth::id();
        
        // Kiểm tra spam trước khi tạo bài post
        $this->checkSpam($userId);
        
        $data['user_id'] = $userId;
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



