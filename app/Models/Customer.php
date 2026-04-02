<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'total_sale',
        'total_paid',
        'total_due',
        'is_active',
    ];

    protected $casts = [
        'total_sale' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'total_due' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function payments()
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function accountsReceivable()
    {
        return $this->hasMany(AccountsReceivable::class);
    }

    public function updateBalance()
    {
        $this->syncSaleBalances();

        $this->total_sale = $this->sales()->sum('total');
        $totalPayments = $this->payments()->sum('amount');
        $totalDiscount = $this->payments()->sum('discount');
        $this->total_paid = $totalPayments + $totalDiscount;
        $this->total_due = max(0, $this->total_sale - $this->total_paid);
        $this->saveQuietly();
    }

    public function syncSaleBalances(): void
    {
        $sales = $this->sales()
            ->with('payments')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        if ($sales->isEmpty()) {
            return;
        }

        $allPayments = $this->payments()->get();

        if ($allPayments->isEmpty()) {
            foreach ($sales as $sale) {
                $calculatedDue = round(max(0, (float) $sale->total - (float) $sale->paid), 2);

                if ((float) $sale->due !== $calculatedDue) {
                    $sale->forceFill([
                        'due' => $calculatedDue,
                    ])->saveQuietly();
                }
            }

            return;
        }

        $carryForwardCredit = (float) $allPayments
            ->whereNull('sale_id')
            ->sum(fn ($payment) => (float) $payment->amount + (float) ($payment->discount ?? 0));

        foreach ($sales as $sale) {
            $explicitCredit = (float) $sale->payments->sum(
                fn ($payment) => (float) $payment->amount + (float) ($payment->discount ?? 0)
            );

            $saleTotal = round((float) $sale->total, 2);
            $availableCredit = round($carryForwardCredit + $explicitCredit, 2);
            $paidAmount = round(min($saleTotal, $availableCredit), 2);
            $remainingDue = round(max(0, $saleTotal - $paidAmount), 2);
            $carryForwardCredit = round(max(0, $availableCredit - $saleTotal), 2);

            if ((float) $sale->paid !== $paidAmount || (float) $sale->due !== $remainingDue) {
                $sale->forceFill([
                    'paid' => $paidAmount,
                    'due' => $remainingDue,
                ])->saveQuietly();
            }
        }
    }

    public function updateTotals()
    {
        $this->total_sales = $this->accountsReceivable()->sum('total_amount');
        $this->total_paid = $this->accountsReceivable()->sum('paid_amount');
        $this->total_due = $this->accountsReceivable()->sum('outstanding_amount');
        $this->save();
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d');
    }
}
