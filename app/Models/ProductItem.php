<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductItem extends Model
{
    use HasFactory;
    
    protected $guarded = ['id'];
    protected $table = 'product_items';

    public function product() {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
