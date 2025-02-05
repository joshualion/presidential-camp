<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
   
    protected $fillable = [
        'post_id',
        'user_id',
        'content',
        'parent_id',
    ];

    // Relationship to Post
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    // Relationship to User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship to Parent Comment (for nested comments)
    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    // Relationship to Replies (child comments)
    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }
}
