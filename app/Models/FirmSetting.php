<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FirmSetting extends Model
{
    public const DEFAULT_NAME = 'Legal Desk';

    public $timestamps = false;

    public $incrementing = false;

    public static function displayName(): string
    {
        return static::query()->whereKey(1)->value('firm_name') ?? self::DEFAULT_NAME;
    }

    public static function taskWarningHours(): int
    {
        return (int) (static::query()->whereKey(1)->value('task_warning_hours') ?? 3);
    }

    public static function setTaskWarningHours(int $hours): void
    {
        static::query()->upsert([['id' => 1, 'task_warning_hours' => $hours]], ['id'], ['task_warning_hours']);
    }

    public static function setName(?string $name): void
    {
        static::query()->upsert([['id' => 1, 'firm_name' => $name]], ['id'], ['firm_name']);
    }
}
