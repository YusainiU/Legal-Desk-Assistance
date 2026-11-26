<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FirmSetting extends Model
{
    public const DEFAULT_NAME = 'Legal Desk';

    public const CASE_AUDIT_RETENTION = [
        0 => 'Indefinite (no automatic deletion)',
        90 => '90 days',
        180 => '180 days',
        365 => '1 year',
        730 => '2 years',
        1825 => '5 years',
        2555 => '7 years',
    ];

    public static function caseAuditRetentionDays(): int
    {
        $days = (int) static::query()->whereKey(1)->value('case_audit_retention_days');

        return array_key_exists($days, self::CASE_AUDIT_RETENTION) ? $days : 0;
    }

    public static function setCaseAuditRetentionDays(int $days): void
    {
        if (! array_key_exists($days, self::CASE_AUDIT_RETENTION)) {
            throw new \InvalidArgumentException('Unsupported audit retention period.');
        }

        static::query()->upsert([
            ['id' => 1, 'case_audit_retention_days' => $days === 0 ? null : $days],
        ], ['id'], ['case_audit_retention_days']);
    }

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
