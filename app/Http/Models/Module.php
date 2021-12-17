<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $table = "modules";
    public $guarded = ["created_at"];
    public $casts = [
        "data" => "object"
    ];
}
