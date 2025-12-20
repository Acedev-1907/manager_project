<?php

namespace App\Repositories;

use App\Models\Post;

class PostRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return Post::class;
    }

    public function getAllPostsWithUser()
    {
        $userId = \Illuminate\Support\Facades\Auth::id();
        
        return $this->model->with([
            'user:id,name,avatar',
            'likes:id,post_id,user_id,type', // Include type field
            'likes.user:id,name,avatar',
            'comments' => function($query) {
                $query->whereNull('parent_id')
                    ->with(['user:id,name,avatar', 'replies.user:id,name,avatar'])
                    ->orderBy('created_at', 'asc');
            }
        ])
            ->withCount('likes')
            ->withCount('comments')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function getPostById($id)
    {
        return $this->model->with([
            'user:id,name,avatar',
            'likes:id,post_id,user_id,type', // Include type field
            'likes.user:id,name,avatar',
            'comments' => function($query) {
                $query->whereNull('parent_id')
                    ->with(['user:id,name,avatar', 'replies.user:id,name,avatar'])
                    ->orderBy('created_at', 'asc');
            }
        ])
            ->withCount('likes')
            ->withCount('comments')
            ->findOrFail($id);
    }

    public function getUserImages($userId)
    {
        return $this->model->where('user_id', $userId)
            ->where(function($query) {
                $query->whereNotNull('image_url')
                      ->orWhereNotNull('images');
            })
            ->orderBy('created_at', 'desc')
            ->select('id', 'image_url', 'images', 'created_at')
            ->get()
            ->flatMap(function ($post) {
                $images = [];
                
                // Add old image_url if exists
                if ($post->image_url) {
                    $images[] = [
                        'id' => $post->id . '_old',
                        'url' => $post->image_url,
                        'created_at' => $post->created_at,
                    ];
                }
                
                // Add images from array if exists
                if ($post->images && is_array($post->images)) {
                    foreach ($post->images as $index => $imageUrl) {
                        $images[] = [
                            'id' => $post->id . '_' . $index,
                            'url' => $imageUrl,
                            'created_at' => $post->created_at,
                        ];
                    }
                }
                
                return $images;
            })
            ->values()
            ->all();
    }
}



