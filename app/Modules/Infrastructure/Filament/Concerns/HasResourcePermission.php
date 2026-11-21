<?php

declare(strict_types=1);

namespace App\Modules\Infrastructure\Filament\Concerns;

trait HasResourcePermission
{
    abstract protected static function getPermissionPrefix(): string;

    public static function canViewAny(): bool
    {
        return static::checkPermission('view');
    }

    public static function canCreate(): bool
    {
        return static::checkPermission('create');
    }

    public static function canEdit($record): bool
    {
        return static::checkPermission('edit');
    }

    public static function canDelete($record): bool
    {
        return static::checkPermission('delete');
    }

    public static function canView($record): bool
    {
        return static::checkPermission('view');
    }

    protected static function checkPermission(string $action): bool
    {
        $user = auth('admin')->user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('super_admin', 'admin')) {
            return true;
        }

        return $user->can(static::getPermissionPrefix().'.'.$action);
    }
}
