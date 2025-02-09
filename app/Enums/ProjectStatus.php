<?php

namespace App\Enums;

enum ProjectStatus: int
{
    case ACTIVE = 1;
    case PENDING = 2;
    case COMPLETED = 3;

    public static function values(): array
    {
        return [
            self::ACTIVE->value,
            self::PENDING->value,
            self::COMPLETED->value,
        ];
    }

    public static function labels(): array
    {
        return [
            self::ACTIVE->value => 'Active',
            self::PENDING->value => 'Pending',
            self::COMPLETED->value => 'Completed',
        ];
    }

    public static function fromString(string $status): ?self
    {
        return match (strtolower($status)) {
            'active' => self::ACTIVE,
            'pending' => self::PENDING,
            'completed' => self::COMPLETED,
            default => null,
        };
    }

    public function label(): string
    {
        return self::labels()[$this->value] ?? 'Unknown';
    }
}