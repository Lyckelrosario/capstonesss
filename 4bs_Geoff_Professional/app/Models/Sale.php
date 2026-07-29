<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    protected $fillable = ['product_id', 'quantity', 'total', 'sold_at', 'note'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'total' => 'decimal:2',
            'sold_at' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
