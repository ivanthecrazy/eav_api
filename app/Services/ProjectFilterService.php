<?php

namespace App\Services;

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
        $attributeNames = [];

        foreach ($filters as $key => $value) {
            [$field, $operation] = $this->extractFilterComponents($key);
            
            if ($this->isRegularField($field)) {
                $regularFilters[$key] = $value;
            } else {
                $eavFilters[$key] = $value;
                $attributeNames[] = $field;
            }
        }

        $attributes = Attribute::whereIn('name', array_keys($attributeNames))
            ->get()
            ->keyBy('name');

        foreach ($regularFilters as $key => $value) {
            [$field, $operation] = $this->extractFilterComponents($key);
            $this->applyRegularFilter($query, $field, $this->operators[$operation] ?? '=', $value);
        }

        foreach ($eavFilters as $key => $value) {
            [$field, $operation] = $this->extractFilterComponents($key);
            if (isset($attributes[$field])) {
                $this->applyEAVFilter($query, $field, $this->operators[$operation] ?? '=', $value, $attributes[$field]);
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

            if ($attribute->type === 'number' || $attribute->type === 'date') {
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