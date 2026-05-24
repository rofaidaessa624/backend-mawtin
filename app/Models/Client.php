<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Client extends Authenticatable  // غيرنا من Model لـ Authenticatable عشان نقدر نعمله تسجيل دخول
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
protected $fillable = [
    'full_name', 'phone', 'phone2', 'national_id', 'password',
    'address', 'gender', 'broker_name', 'broker_phone',
    'is_active', 'user_id'
];
    
    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password'
    ];

    /**
     * Boot function to set default values
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($client) {
            if (is_null($client->user_id)) {
                $client->user_id = 1; // قيمة افتراضية للـ user_id
            }
        });
    }

    /**
     * Relationships
     */
    
    // العلاقة مع جدول users (الموظف اللي أضاف العميل)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // العلاقة مع جدول units (الوحدات المملوكة للعميل)
    public function units()
    {
        return $this->belongsToMany(Unit::class, 'client_unit')
                    ->withPivot('agreed_price', 'paid_amount', 'purchase_date', 'contract_status')
                    ->withTimestamps();
    }

    // العلاقة مع جدول installments (الأقساط)
    public function installments()
    {
        return $this->hasMany(Installment::class);
    }

    // العلاقة مع جدول comments (التعليقات اللي ضافها العميل)
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    // العلاقة مع جدول unit_updates (لو عايز تجلب تطورات الوحدات اللي يملكها)
    public function unitUpdates()
    {
        return $this->hasManyThrough(UnitUpdate::class, Unit::class, 'id', 'unit_id', 'id', 'id');
    }

    /**
     * Helper Methods
     */
    
    // جلب مجموع المدفوعات
    public function getTotalPaidAttribute()
    {
        return $this->installments()->where('status', 'paid')->sum('paid_amount');
    }

    // جلب المبلغ المتبقي
    public function getTotalRemainingAttribute()
    {
        $totalPrice = $this->units()->sum('total_price');
        return $totalPrice - $this->getTotalPaidAttribute();
    }

    // جلب الأقساط المتأخرة
    public function overdueInstallments()
    {
        return $this->installments()
            ->where('status', 'overdue')
            ->orWhere(function($query) {
                $query->where('status', 'pending')
                      ->where('due_date', '<', now());
            })
            ->get();
    }

    // جلب الأقساط المستحقة خلال الأيام القادمة
    public function upcomingInstallments($days = 10)
    {
        return $this->installments()
            ->where('status', 'pending')
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays($days))
            ->orderBy('due_date')
            ->get();
    }


public function notifications()
{
    return $this->hasMany(Notification::class);
}

}