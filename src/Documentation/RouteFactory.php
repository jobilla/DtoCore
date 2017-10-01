<?php

namespace Jobilla\DtoCore\Documentation;

use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Collection;

abstract class RouteFactory
{
    /**
     * @return Collection|Route[]
     */
    public static function fromLaravelRoutes(string $includePrefix): Collection
    {
        return collect(\Route::getRoutes())
            // Include only routes with given path prefix
            ->filter(function (LaravelRoute $route) use ($includePrefix) {
                return strpos($route->getPath(), $includePrefix) === 0;
            })
            // Do not include routes that don't have a concrete controller action
            ->filter(function (LaravelRoute $route) use ($includePrefix) {
                return strpos($route->getActionName(), '@') !== false;
            })
            // Map Laravel Routes to local Route instance
            ->map(function (LaravelRoute $route) use ($includePrefix) {
                return self::create($route, $includePrefix);
            })
            // Filter out empty rows (?)
            ->filter()
            // Sort routes (?)
            ->sort();
    }

    /**
     * @param Route $route
     *
     * @return Route
     */
    public static function create(LaravelRoute $route, string $prefix): Route
    {
        return new Route($route, $prefix);
    }
}
