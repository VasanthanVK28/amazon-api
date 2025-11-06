<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LayoutSetting extends Model
{
    protected $fillable = [
        'show_price', 'show_rating', 'show_labels',
        'visible_count', 'card_color', 'text_color', 'star_color'
    ];
}
