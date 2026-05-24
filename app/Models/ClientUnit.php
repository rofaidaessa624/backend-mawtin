<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientUnit extends Model
{
    protected $table = 'client_unit';

    protected $fillable = [
        'client_id',
        'unit_id',
        'agreed_price',
        'paid_amount',
        'purchase_date',
        'contract_status',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'agreed_price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}