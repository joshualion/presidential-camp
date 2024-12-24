<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;
    protected $table = 'system_settings';
    protected $guarded =[];


    protected static function boot()
    {
        parent::boot();

        static::updated(function ($model) {
            \Artisan::call('route:clear');
            \Artisan::call('route:cache');
        });
    }
}
