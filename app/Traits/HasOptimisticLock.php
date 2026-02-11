<?php

namespace App\Traits;

use App\Exceptions\StaleObjectException;

trait HasOptimisticLock
{
    /**
     * Boot the trait.
     */
    protected static function bootHasOptimisticLock(): void
    {
        static::saving(function ($model) {
            if ($model->exists && $model->isDirty() && !$model->isDirty('version')) {
                $model->increment('version');
            }
        });
    }

    /**
     * Check version and throw exception if stale.
     */
    public function checkVersion(?int $requestVersion): void
    {
        if ($requestVersion !== null && $this->version !== $requestVersion) {
            throw new StaleObjectException(
                "This {$this->getTable()} has been modified by another user. Expected version {$requestVersion}, but current version is {$this->version}."
            );
        }
    }
}
