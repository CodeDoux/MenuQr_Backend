<?php

namespace App\Models\Scopes;

use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use RuntimeException;

class RestaurantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenant = app(TenantContext::class);

        if (! $tenant->estDefini()) {
            throw new RuntimeException(
                'RestaurantScope actif sur '.get_class($model).' sans contexte tenant défini. '.
                'Utilisez withoutGlobalScope(RestaurantScope::class) explicitement si ce comportement est voulu (ex. seeders, console).'
            );
        }

        $builder->where($model->getTable().'.restaurant_id', $tenant->restaurantId);
    }
}