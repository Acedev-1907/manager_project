<?php

namespace App\Repositories;

use App\Models\Post;

class PostRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return Post::class;
    }

    public function getAllPostsWithUser(?int $filterUserId = null, ?int $viewerUserId = null)
    {
        $query = $this->model->with([
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
            ->withCount('comments');
        
        // Filter by user_id if provided (for profile page)
        if ($filterUserId !== null) {
            $query->where('user_id', $filterUserId);
        }
        
        // Filter by privacy settings
        if ($viewerUserId !== null) {
            $query->where(function($q) use ($viewerUserId) {
                // Public posts: everyone can see
                $q->where('privacy', 'public');
                
                // Private posts: only the post owner can see
                $q->orWhere(function($subQ) use ($viewerUserId) {
                    $subQ->where('privacy', 'private')
                         ->where('user_id', $viewerUserId);
                });
                
                // Friends posts: only friends of the post owner can see
                $q->orWhere(function($subQ) use ($viewerUserId) {
                    $subQ->where('privacy', 'friends')
                         ->where(function($friendQ) use ($viewerUserId) {
                             // Viewer is the post owner
                             $friendQ->where('user_id', $viewerUserId)
                                    // OR viewer is a friend of the post owner (check both directions in members table)
                                    ->orWhereExists(function($memberQuery) use ($viewerUserId) {
                                        $memberQuery->select(\DB::raw(1))
                                                   ->from('members')
                                                   ->where(function($m) use ($viewerUserId) {
                                                       // Check if viewer is in post owner's friend list
                                                       $m->whereColumn('members.user_id', 'posts.user_id')
                                                         ->where('members.member_id', $viewerUserId);
                                                   })
                                                   ->orWhere(function($m) use ($viewerUserId) {
                                                       // Check if post owner is in viewer's friend list (bidirectional)
                                                       $m->whereColumn('members.member_id', 'posts.user_id')
                                                         ->where('members.user_id', $viewerUserId);
                                                   });
                                    });
                         });
                });
            });
        } else {
            // If no viewer, only show public posts
            $query->where('privacy', 'public');
        }
        
        return $query->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function getPostById($id, ?int $viewerUserId = null)
    {
        $query = $this->model->with([
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
            ->where('id', $id);
        
        // Filter by privacy settings
        if ($viewerUserId !== null) {
            $query->where(function($q) use ($viewerUserId) {
                // Public posts: everyone can see
                $q->where('privacy', 'public');
                
                // Private posts: only the post owner can see
                $q->orWhere(function($subQ) use ($viewerUserId) {
                    $subQ->where('privacy', 'private')
                         ->where('user_id', $viewerUserId);
                });
                
                // Friends posts: only friends of the post owner can see
                $q->orWhere(function($subQ) use ($viewerUserId) {
                    $subQ->where('privacy', 'friends')
                         ->where(function($friendQ) use ($viewerUserId) {
                             // Viewer is the post owner
                             $friendQ->where('user_id', $viewerUserId)
                                    // OR viewer is a friend of the post owner (check both directions in members table)
                                    ->orWhereExists(function($memberQuery) use ($viewerUserId) {
                                        $memberQuery->select(\DB::raw(1))
                                                   ->from('members')
                                                   ->where(function($m) use ($viewerUserId) {
                                                       // Check if viewer is in post owner's friend list
                                                       $m->whereColumn('members.user_id', 'posts.user_id')
                                                         ->where('members.member_id', $viewerUserId);
                                                   })
                                                   ->orWhere(function($m) use ($viewerUserId) {
                                                       // Check if post owner is in viewer's friend list (bidirectional)
                                                       $m->whereColumn('members.member_id', 'posts.user_id')
                                                         ->where('members.user_id', $viewerUserId);
                                                   });
                                    });
                         });
                });
            });
        } else {
            // If no viewer, only show public posts
            $query->where('privacy', 'public');
        }
        
        return $query->firstOrFail();
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



