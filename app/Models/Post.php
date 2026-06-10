<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $guarded = [];

    protected $appends = [
        'image_full_url',
        'video_full_url',
    ];

      // Define the inverse of the relationship
      public function user()
      {
          return $this->belongsTo(User::class);
      }

      public function comments()
      {
          return $this->hasMany(Comment::class);
      }

      public function likedBy()
      {
          return $this->belongsToMany(User::class, 'post_likes')->withTimestamps();
      }

      public function getImageFullUrlAttribute(): ?string
      {
          if (!$this->image_url) {
              return null;
          }

          $path = 'uploads/community/photos/' . ltrim($this->image_url, '/');
          return app(\App\Services\FileStorageService::class)->getFileUrl($path);
      }

      public function getVideoFullUrlAttribute(): ?string
      {
          if (!$this->video_url) {
              return null;
          }

          $path = 'uploads/community/videos/' . ltrim($this->video_url, '/');
          return app(\App\Services\FileStorageService::class)->getFileUrl($path);
      }
    
}
