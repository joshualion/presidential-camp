<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberImportRowError extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'row_data' => 'array',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(MemberImportBatch::class, 'member_import_batch_id');
    }
}
