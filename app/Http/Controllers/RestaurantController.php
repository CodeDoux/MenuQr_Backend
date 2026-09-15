<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\RestaurantInfosRequest;
use App\Http\Resources\RestaurantInfosResource;
use App\Models\Restaurant;
use App\Services\MenuPublicCacheService;
use App\Services\TenantContext;

class RestaurantController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly MenuPublicCacheService $cache
    ) {}

    public function show()
    {
        $restaurant = Restaurant::findOrFail($this->tenant->restaurantId);

        return new RestaurantInfosResource($restaurant);
    }

    public function update(RestaurantInfosRequest $request)
    {
        if (! $this->tenant->aLaPermission('parametre.gerer')) {
            abort(403, 'Action réservée au Propriétaire.');
        }

        $restaurant = Restaurant::findOrFail($this->tenant->restaurantId);
        $restaurant->update($request->validated());

        $this->cache->invalider($this->tenant->restaurantId);

        return new RestaurantInfosResource($restaurant);
    }
}