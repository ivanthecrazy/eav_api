<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\AttributeType;

class Attribute extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type'];

    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }

    protected $casts = [
        'type' => AttributeType::class
    ];
}
