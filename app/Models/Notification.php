<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'title',
        'message',
        'type',
        'is_read',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    protected $casts = [
    'is_read' => 'boolean',
];

public function unit()
{
    return $this->belongsTo(Unit::class);
}

}