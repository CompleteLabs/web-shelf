<?php

namespace App\Enums;

enum AssetCondition: string
{
    case Available = 'available';
    case Transferred = 'transferred';
    case Lost = 'lost';
    case Damaged = 'damaged';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tersedia',
            self::Transferred => 'Digunakan',
            self::Lost => 'Hilang',
            self::Damaged => 'Rusak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available => 'success',
            self::Transferred => 'warning',
            self::Lost => 'danger',
            self::Damaged => 'danger',
        };
    }

    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
