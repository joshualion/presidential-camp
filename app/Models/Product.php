<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function Productcategory(){
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function Orders(){
        return $this->hasMany(ProductOrder::class, 'product_id');
    }

    public function invoices(){
        return $this->hasMany(Invoice::class, 'product_id');
    }

}
