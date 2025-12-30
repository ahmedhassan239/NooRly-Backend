<?php

namespace App\Observers;

use App\Domain\Audit\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ExternalContentObserver
{
    public function created(Model $model): void
    {
        $this->logAction($model, 'create');
    }

    public function updated(Model $model): void
    {
        $this->logAction($model, 'update');
    }

    public function deleted(Model $model): void
    {
        $this->logAction($model, 'delete');
    }

    protected function logAction(Model $model, string $action): void
    {
        AuditLog::create([
            'user_id' => Auth::id(), // Null if system action
            'action' => $action,
            'resource_type' => get_class($model),
            'resource_id' => $model->getKey(),
            'before_state' => $action === 'update' ? array_intersect_key($model->getOriginal(), $model->getDirty()) : ($action === 'delete' ? $model->toArray() : null),
            'after_state' => $action !== 'delete' ? $model->getDirty() : null,
        ]);
    }
}
