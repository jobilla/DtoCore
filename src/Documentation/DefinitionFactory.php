<?php

namespace Jobilla\DtoCore\Documentation;

use Illuminate\Support\Collection;

abstract class DefinitionFactory
{
    /**
     * @return Collection|Definition[]
     */
    public static function fromRoutes(Collection $routes): Collection
    {
        return $routes
            ->flatMap(function (Route $route) {
                return $route->getIoClasses();
            })
            ->unique()
            ->map(function (string $className) {
                return self::create($className);
            });
    }

    /**
     * @param string $className
     *
     * @return Definition
     */
    public static function create(string $className): Definition
    {
        return new Definition($className);
    }
}
