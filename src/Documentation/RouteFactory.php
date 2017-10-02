<?php

namespace Jobilla\DtoCore\Documentation;

use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Collection;

abstract class RouteFactory
{
    /**
     * @param string $includePrefix
     *
     * @return Collection
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
            ->sort();
    }

    /**
     * @param LaravelRoute $route
     * @param string       $prefix
     *
     * @return Route
     */
    public static function create(LaravelRoute $route, string $prefix): Route
    {
        return new Route($route, $prefix);
    }
}
