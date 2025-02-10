<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\AttributeValue;

class EAVService
{
    /**
     * Store or update attributes for an entity using bulk upsert.
     *
     * @param int|string $entityId
     * @param array $attributes
     */
    public function upsertAttributes($entityId, array $attributes)
    {
        // I wasn't sure if we should allow to pass attributes which don't exist in the database.
        // But this will require passing the attribute type as well or guessing it from the value.
        // So I decided to only upsert attributes that exist in the database.
        $attributeIds = Attribute::whereIn('name', collect($attributes)->pluck('name'))->get()->pluck('id', 'name');

        $data = [];
        foreach ($attributes as $attributeData) {
            if (isset($attributeIds[$attributeData['name']])) {
                $data[] = [
                    'attribute_id' => $attributeIds[$attributeData['name']],
                    'entity_id' => $entityId,
                    'value' => $attributeData['value']
                ];
            }
        }

        if (!empty($data)) {
            AttributeValue::upsert($data, ['attribute_id', 'entity_id'], ['value']);
        }
    }
}