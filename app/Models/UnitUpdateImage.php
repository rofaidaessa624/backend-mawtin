<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitUpdateImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_update_id',
        'path',
                'file_type', // ✅ أضف هذا

    ];

    public function unitUpdate()
    {
        return $this->belongsTo(UnitUpdate::class);
    }
}