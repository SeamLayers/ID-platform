<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_card_id',
        'interaction_type',
        'source',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function businessCard()
    {
        return $this->belongsTo(BusinessCard::class);
    }

}
