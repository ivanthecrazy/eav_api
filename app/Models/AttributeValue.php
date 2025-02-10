<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    protected $fillable = ['attribute_id', 'entity_id', 'value'];

    protected $appends = ['attribute_name'];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'entity_id');
    }

    public function getAttributeNameAttribute()
    {
        return $this->attribute ? $this->attribute->name : null;
    }
}
