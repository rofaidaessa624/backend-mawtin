<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Installment extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'unit_id',
        'installment_number',
        'amount',
        'due_date',
        'status',
        'paid_date',
        'paid_amount',
        'late_fees',
        'notes'
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'late_fees' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_PARTIALLY_PAID = 'partially_paid';
    const STATUS_CANCELLED = 'cancelled';

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    // Accessors & Mutators
    public function getRemainingAmountAttribute()
    {
        return $this->amount - ($this->paid_amount ?? 0);
    }

    public function getIsOverdueAttribute()
    {
        return $this->status === self::STATUS_OVERDUE || 
               ($this->status !== self::STATUS_PAID && 
                $this->due_date < now() && 
                $this->status !== self::STATUS_CANCELLED);
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            self::STATUS_PAID => '<span class="badge bg-success">مدفوعة</span>',
            self::STATUS_PENDING => '<span class="badge bg-warning">قيد الانتظار</span>',
            self::STATUS_OVERDUE => '<span class="badge bg-danger">متأخرة</span>',
            self::STATUS_PARTIALLY_PAID => '<span class="badge bg-info">مدفوعة جزئياً</span>',
            self::STATUS_CANCELLED => '<span class="badge bg-secondary">ملغاة</span>',
            default => '<span class="badge bg-secondary">غير معروف</span>',
        };
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE);
    }

    public function scopeDueBefore($query, $date)
    {
        return $query->where('due_date', '<=', $date)
                     ->where('status', '!=', self::STATUS_PAID);
    }

    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeByUnit($query, $unitId)
    {
        return $query->where('unit_id', $unitId);
    }

    // Helper Methods
    public function markAsPaid($paidAmount = null, $paidDate = null)
    {
        $amountToPay = $paidAmount ?? $this->amount;
        $datePaid = $paidDate ?? now();

        if ($amountToPay >= $this->amount) {
            $this->status = self::STATUS_PAID;
            $this->paid_amount = $this->amount;
        } else {
            $this->status = self::STATUS_PARTIALLY_PAID;
            $this->paid_amount = ($this->paid_amount ?? 0) + $amountToPay;
        }

        $this->paid_date = $datePaid;
        
        return $this->save();
    }

    public function calculateLateFees($dailyRate = 0, $maxDays = null)
    {
        if ($this->status === self::STATUS_PAID || $this->status === self::STATUS_CANCELLED) {
            return 0;
        }

        $daysOverdue = max(0, now()->diffInDays($this->due_date, false));
        
        if ($maxDays && $daysOverdue > $maxDays) {
            $daysOverdue = $maxDays;
        }

        if ($daysOverdue <= 0) {
            return 0;
        }

        $this->late_fees = $this->amount * ($dailyRate / 100) * $daysOverdue;
        return $this->late_fees;
    }

    public function updateStatusAutomatically()
    {
        if ($this->status === self::STATUS_PAID || $this->status === self::STATUS_CANCELLED) {
            return;
        }

        if ($this->paid_amount >= $this->amount) {
            $this->status = self::STATUS_PAID;
        } elseif ($this->paid_amount > 0) {
            $this->status = self::STATUS_PARTIALLY_PAID;
        } elseif ($this->due_date < now()) {
            $this->status = self::STATUS_OVERDUE;
        } else {
            $this->status = self::STATUS_PENDING;
        }

        $this->save();
    }
}