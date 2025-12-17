<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_no',
        'from_warehouse_id',
        'to_warehouse_id',
        'date',
        'status',
        'note',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    protected static function booted()
    {
        static::creating(function ($transfer) {
            if (!$transfer->transfer_no) {
                $transfer->transfer_no = 'TRF-' . date('Ymd') . '-' . str_pad(static::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Complete the transfer - move stock from one warehouse to another
    public function complete($approverId = null)
    {
        if ($this->status !== 'pending' && $this->status !== 'in_transit') {
            return false;
        }

        foreach ($this->items as $item) {
            // Decrease from source warehouse
            $this->fromWarehouse->updateStock($item->product_id, $item->quantity, false);

            // Increase in destination warehouse
            $this->toWarehouse->updateStock($item->product_id, $item->quantity, true);
        }

        $this->update([
            'status' => 'completed',
            'approved_by' => $approverId,
        ]);

        return true;
    }

    // Cancel the transfer
    public function cancel()
    {
        if ($this->status === 'completed') {
            return false;
        }

        $this->update(['status' => 'cancelled']);
        return true;
    }
}
