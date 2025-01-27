<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $guarded = [];

      // Define the inverse of the relationship
      public function user()
      {
          return $this->belongsTo(User::class);
      }
    
}
