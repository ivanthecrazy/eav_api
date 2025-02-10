<?php

namespace App\Services;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProjectFilterService
{
    /**
     * Supported operators mapping.
     */
    private array $operators = [
        'eq'   => '=',
        'lt'   => '<',
        'lte'  => '<=',
        'gt'   => '>',
        'gte'  => '>=',
        'like' => 'LIKE'
    ];

    /**
     * Apply filters to the project query.
     */
    public function applyFilters(Request $request, Builder $query): Builder
    {
        $filters = $request->query('filter', []);

        if (empty($filters)) {
            return $query;
        }

        $regularFilters = [];
        $eavFilters = [];

        foreach ($filters as $key => $value) {
            [$field, $operation] = $this->extractFilterComponents($key);

            $fieldData = [
                'value' => $value,
                'field' => $field,
                'operation' => $operation
            ];
            
            if ($this->isRegularField($field)) {
                $regularFilters[$key] = $fieldData;
            } else {
                $eavFilters[$key] = $fieldData;
            }
        }

        $attributes = Attribute::whereIn('name', array_column($eavFilters, 'field'))
            ->get()
            ->keyBy('name');

        foreach ($regularFilters as $key => $value) {
            $this->applyRegularFilter($query, $value['field'], $this->operators[$value['operation']] ?? '=', $value['value']);
        }

        foreach ($eavFilters as $key => $value) {
            if (isset($attributes[$value['field']])) {
                $this->applyEAVFilter($query, $value['field'], $this->operators[$value['operation']] ?? '=', $value['value'], $attributes[$value['field']]);
            }
        }

        return $query;
    }

    /**
     * Extract attribute name and operator from the filter key.
     */
    private function extractFilterComponents(string $key): array
    {
        $parts = explode('_', $key);
        $operation = array_pop($parts);
        $field = implode('_', $parts);

        return [$field, $operation];
    }

    /**
     * Determine if the field is a regular column in the projects table.
     */
    private function isRegularField(string $field): bool
    {
        return in_array($field, ['name', 'status', 'first_name', 'created_at', 'updated_at']);
    }

    /**
     * Apply filters for standard project fields (e.g., name, status).
     */
    private function applyRegularFilter(Builder $query, string $field, string $operator, string $value): void
    {
        if ($operator === 'LIKE') {
            $value = str_replace('*', '%', $value);
        }

        $query->where($field, $operator, $value);
    }

    /**
     * Apply filters for EAV attributes (e.g., department, start_date).
     */
    private function applyEAVFilter(Builder $query, string $field, string $operator, string $value, Attribute $attribute): void
    {
        $query->whereHas('attributeValues', function ($q) use ($field, $operator, $value, $attribute) {

            if ($attribute->type === AttributeType::NUMBER || $attribute->type === AttributeType::DATE) {
                $q->whereRaw("CAST(value AS DECIMAL(10,2)) $operator ?", [$value]);
            } else {
                if ($operator === 'LIKE') {
                    $value = str_replace('*', '%', $value);
                }
                $q->where('value', $operator, $value);
            }
        });
    }
}