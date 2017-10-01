<?php

namespace Jobilla\DtoCore\Documentation;

use Illuminate\Support\Collection;

class Documentation
{
    /**
     * @param string $apiPathPrefix
     *
     * @return array
     */
    public function get(string $apiPathPrefix): array
    {
        $routes      = RouteFactory::fromLaravelRoutes($apiPathPrefix);
        $tags        = $this->getTags($routes);
        $definitions = $this->getDefinitions($routes);
        $paths       = $this->getPaths($routes);

        return [
            'swagger'     => '2.0',
            'host'        => strtr(env('APP_URL'), ['http://' => '', 'https://' => '']),
            'schemes'     => ['https'],
            'produces'    => ['application/json'],
            'info'        => [
                'version'     => 'v2',
                'title'       => 'API Documentation',
                'description' => 'API v2 endpoints documentation',
            ],
            'definitions' => $definitions,
            'tags'        => $tags,
            'x-tagGroups' => [
                [
                    'name' => 'API endpoints',
                    'tags' => collect($tags)->pluck('name')->toArray(),
                ],
            ],
            'paths'       => $paths,
        ];
    }

    /**
     * @param $routes
     *
     * @return array
     */
    protected function getTags(Collection $routes): array
    {
        return $routes->map(function (Route $route) {
            return ['name' => $route->getTag()];
        })->unique()->values()->toArray();
    }

    /**
     * @param Collection $routes
     *
     * @return array
     */
    protected function getDefinitions(Collection $routes): array
    {
        return DefinitionFactory::fromRoutes($routes)
            ->mapWithKeys(function (Definition $definition) {
                return [$definition->definitionId() => $definition->getStructure()];
            })->toArray();
    }

    /**
     * @param Collection $routes
     *
     * @return array
     */
    protected function getPaths(Collection $routes): array
    {
        return $routes->mapWithKeys(function (Route $route) {
            $return = [
                'summary' => $route->getPath(),
                'tags'    => [$route->getTag()],
            ];

            $return['parameters'] = collect($route->getInputs())->map(function (string $inputTag) {
                return (new IoParameter($inputTag))->getStructure();
            })->toArray();

            if ($route->getOutputs()) {
                $return['responses'][200] = (new IoParameter($route->getOutputs()[0]))->getStructure();
            }

            return [$route->getPath() => [$route->getHttpMethod() => $return]];
        })->toArray();
    }
}
