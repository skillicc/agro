<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class AccountsReceivablePayment extends Model
{
    protected $table = 'accounts_receivable_payments';

    protected $fillable = [
        'accounts_receivable_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_no',
        'note',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function accountsReceivable()
    {
        return $this->belongsTo(AccountsReceivable::class);
    }

    protected static function booted()
    {
        static::created(function ($model) {
            $model->accountsReceivable->updateBalance();
        });

        static::updated(function ($model) {
            $model->accountsReceivable->updateBalance();
        });

        static::deleted(function ($model) {
            $model->accountsReceivable->updateBalance();
        });
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}
