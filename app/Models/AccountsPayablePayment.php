<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountsPayablePayment extends Model
{
    protected $table = 'accounts_payable_payments';

    protected $fillable = [
        'accounts_payable_id',
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

    public function accountsPayable()
    {
        return $this->belongsTo(AccountsPayable::class);
    }

    protected static function booted()
    {
        static::created(function ($model) {
            $model->accountsPayable->updateBalance();
        });

        static::updated(function ($model) {
            $model->accountsPayable->updateBalance();
        });

        static::deleted(function ($model) {
            $model->accountsPayable->updateBalance();
        });
    }
}
