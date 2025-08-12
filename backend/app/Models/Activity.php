<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'status',
        'adult_agent_commission',
        'adult_staff_commission',
        'adult_boat_boy_commission',
        'adult_total_commission',
        'adult_admin_share',
        'child_agent_commission',
        'child_staff_commission',
        'child_boat_boy_commission',
        'child_total_commission',
        'child_admin_share',
        'gst_rate',
    ];
}


