<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'payment_history';

    protected $fillable = [
        'client_id', 'unit_id', 'installment_id', 'amount',
        'payment_method', 'receipt_number', 'notes', 'recorded_by',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function installment()
    {
        return $this->belongsTo(Installment::class);
    }
}