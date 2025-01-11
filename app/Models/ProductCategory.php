<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $guarded = [];

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }


    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function orders(){
        return $this->hasMany(ProductOrder::class, 'product_category_id');
    }
}
