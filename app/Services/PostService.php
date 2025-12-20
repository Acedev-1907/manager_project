<?php

namespace App\Services;

use App\Repositories\PostRepository;
use App\Models\PostLike;
use App\Models\PostComment;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\SpamDetectionService;

/**
 * Post Service
 * 
 * Handles all business logic related to posts including creation,
 * retrieval, likes, comments, and sharing.
 */
class PostService
{
    protected PostRepository $postRepository;
    protected SpamDetectionService $spamDetection;

    /**
     * Initialize the service
     * 
     * @param PostRepository $postRepository
     * @param SpamDetectionService $spamDetection
     */
    public function __construct(PostRepository $postRepository, SpamDetectionService $spamDetection)
    {
        $this->postRepository = $postRepository;
        $this->spamDetection = $spamDetection;
    }

    /**
     * Get all posts with user information, likes, and comments
     * 
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllPosts()
    {
        return $this->postRepository->getAllPostsWithUser();
    }

    /**
     * Get a specific post by ID with relations
     * 
     * @param int $id
     * @return Post
     */
    public function getPostById(int $id): Post
    {
        return $this->postRepository->getPostById($id);
    }

    /**
     * Create a new post with spam detection
     * 
     * @param array $data Post data (content, images, etc.)
     * @param Request|null $request HTTP request object
     * @return Post
     * @throws \Exception
     */
    public function createPost(array $data, ?Request $request = null): Post
    {
        $userId = Auth::id();
        
        // Check spam using multiple factors (User ID, IP, fingerprint)
        $this->spamDetection->checkPostSpam($userId, $request);
        
        $data['user_id'] = $userId;
        return $this->postRepository->create($data);
    }

    /**
     * Get user's images from their posts
     * 
     * @param int $userId
     * @return array
     */
    public function getUserImages(int $userId): array
    {
        return $this->postRepository->getUserImages($userId);
    }

    /**
     * Toggle reaction on a post
     * 
     * Logic:
     * - If no like exists -> create new like with type
     * - If like exists with same type -> delete (unlike)
     * - If like exists with different type -> update to new type
     * 
     * @param int $postId
     * @param string $type Reaction type (like, love, etc.)
     * @return array
     */
    public function toggleLike(int $postId, string $type = 'like'): array
    {
        $userId = Auth::id();
        
        $like = PostLike::where('post_id', $postId)
            ->where('user_id', $userId)
            ->first();

        if ($like) {
            if ($like->type === $type) {
                // Same type -> unlike (delete)
                $like->delete();
                return ['liked' => false, 'type' => null];
            }

            // Different type -> update to new type
            $like->type = $type;
            $like->save();
            return ['liked' => true, 'type' => $type];
        }

        // No like exists -> create new
        PostLike::create([
            'post_id' => $postId,
            'user_id' => $userId,
            'type' => $type,
        ]);

        return ['liked' => true, 'type' => $type];
    }

    /**
     * Add a comment to a post with spam detection
     * 
     * @param int $postId
     * @param string $content Comment content
     * @param Request|null $request HTTP request object
     * @return PostComment
     * @throws \Exception
     */
    public function addComment(int $postId, string $content, ?Request $request = null): PostComment
    {
        $userId = Auth::id();
        
        // Check spam for comment creation
        $this->spamDetection->checkCommentSpam($userId, $request);
        
        $comment = PostComment::create([
            'post_id' => $postId,
            'user_id' => $userId,
            'content' => $content,
        ]);
        
        // Load user relationship for response
        return $comment->load('user:id,name,avatar');
    }

    /**
     * Reply to a comment
     * 
     * @param int $commentId
     * @param string $content Reply content
     * @param Request|null $request HTTP request object
     * @return PostComment
     * @throws \Exception
     */
    public function replyComment(int $commentId, string $content, ?Request $request = null): PostComment
    {
        $userId = Auth::id();
        
        // Get parent comment to get post_id
        $parentComment = PostComment::findOrFail($commentId);
        
        // Check spam for comment creation
        $this->spamDetection->checkCommentSpam($userId, $request);
        
        $reply = PostComment::create([
            'post_id' => $parentComment->post_id,
            'user_id' => $userId,
            'content' => $content,
            'parent_id' => $commentId,
        ]);
        
        // Load user relationship for response
        return $reply->load('user:id,name,avatar');
    }

    /**
     * Increment share count for a post
     * 
     * @param int $postId
     * @return int Updated share count
     */
    public function sharePost(int $postId): int
    {
        $post = $this->postRepository->getPostById($postId);
        $post->share_count = ($post->share_count ?? 0) + 1;
        $post->save();

        return $post->share_count;
    }
}
