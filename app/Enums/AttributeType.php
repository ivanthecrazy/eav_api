<?php

namespace App\Enums;

enum AttributeType: int
{
    case TEXT = 1;
    case DATE = 2;
    case NUMBER = 3;
    case SELECT = 4;

    public static function fromInt(int $value): self
    {
        return match ($value) {
            1 => self::TEXT,
            2 => self::DATE,
            3 => self::NUMBER,
            4 => self::SELECT,
            default => throw new \InvalidArgumentException("Invalid attribute type: $value"),
        };
    }

    public function toInt(): int
    {
        return $this->value;
    }

    public static function values(): array
    {
        return [
            self::TEXT->value,
            self::DATE->value,
            self::NUMBER->value,
            self::SELECT->value,
        ];
    }
}