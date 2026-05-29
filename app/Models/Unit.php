<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_number',
        'unit_type',
        'total_price',
        'down_payment',
        'number_of_installments',
        'location',
        'area',
        'bedrooms',
        'bathrooms',
        'status',
        'description',
    ];

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'client_unit')
                    ->withPivot('agreed_price', 'paid_amount', 'purchase_date', 'contract_status')
                    ->withTimestamps();
    }
    public function client()
{
    return $this->belongsTo(Client::class);
}

    public function installments()
    {
        return $this->hasMany(Installment::class);
    }

    public function updates()
    {
        return $this->hasMany(UnitUpdate::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}