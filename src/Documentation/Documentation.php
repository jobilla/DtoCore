<?php

namespace Jobilla\DtoCore\Documentation;

use Illuminate\Support\Collection;

class Documentation
{
    /**
     * @var Collection|Route[]
     */
    protected $routes;

    /**
     * @param string $apiPathPrefix
     * @param string $apiVersion
     *
     * @return array
     */
    public function get(string $apiPathPrefix, string $apiVersion): array
    {
        $this->initRoutes($apiPathPrefix);
        $tags = $this->getTags();

        return [
            'swagger'     => '2.0',
            'host'        => strtr(env('APP_URL'), ['http://' => '', 'https://' => '']),
            'schemes'     => ['https'],
            'produces'    => ['application/json'],
            'info'        => [
                'version'     => $apiVersion,
                'title'       => 'API Documentation',
                'description' => 'API ' . $apiVersion . ' endpoints documentation',
            ],
            'definitions' => $this->getDefinitions(),
            'tags'        => $tags,
            'x-tagGroups' => [
                [
                    'name' => 'API endpoints',
                    'tags' => collect($tags)->pluck('name')->toArray(),
                ],
            ],
            'paths'       => $this->getPaths(),
        ];
    }

    /**
     * @param string $apiPathPrefix
     *
     * @return Documentation
     */
    protected function initRoutes(string $apiPathPrefix): Documentation
    {
        $this->routes = RouteFactory::fromLaravelRoutes($apiPathPrefix);

        return $this;
    }

    /**
     * @return array
     */
    protected function getTags(): array
    {
        return $this->routes->map(function (Route $route) {
            return ['name' => $route->getTag()];
        })->unique()->values()->toArray();
    }

    /**
     * @return array
     */
    protected function getDefinitions(): array
    {
        return DefinitionFactory::fromRoutes($this->routes)
            ->mapWithKeys(function (Definition $definition) {
                return [$definition->definitionId() => $definition->getStructure()];
            })->toArray();
    }

    /**
     * @return array
     */
    protected function getPaths(): array
    {
        $paths = [];

        foreach ($this->routes as $route) {
            $structure = [
                'summary' => $route->getPath(),
                'tags'    => [$route->getTag()],
            ];

            $structure['parameters'] = collect($route->getInputs())->map(function (string $inputTag) {
                return (new IoParameter($inputTag))->getStructure();
            })->toArray();

            if ($route->getOutputs()) {
                $structure['responses'][200] = (new IoParameter($route->getOutputs()[0]))->getStructure();
            }

            $paths[$route->getPath()][$route->getHttpMethod()] = $structure;
        }

        return $paths;
    }
}
