<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\ProjectStatus;

class Project extends Model
{
    protected $fillable = ['name', 'status'];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function timesheets()
    {
        return $this->hasMany(Timesheet::class);
    }

    public function attributeValues()
    {
        return $this->hasMany(AttributeValue::class, 'entity_id');
    }

    protected $casts = [
        'status' => ProjectStatus::class,
    ];

    /**
     * Get a dynamic attribute value
     */
    public function __get($key)
    {
        $attribute = Attribute::where('name', $key)->first();
        if ($attribute) {
            $attributeValue = AttributeValue::where('attribute_id', $attribute->id)
                ->where('entity_id', $this->id)
                ->first();

            return $attributeValue ? $attributeValue->value : null;
        }

        return parent::__get($key);
    }
}
