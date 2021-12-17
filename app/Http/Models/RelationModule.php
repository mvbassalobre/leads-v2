<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class RelationModule extends Model
{
    protected $table = "relation_modules";
    public $guarded = ["created_at"];
    public $casts = [
        "data" => "object"
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}
