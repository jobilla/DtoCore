<?php

namespace Jobilla\DtoCore\Commands;

use Illuminate\Support\Collection;
use Jobilla\DtoCore\Documentation\RouteFactory;
use Jobilla\DtoCore\Documentation\Route as Route;
use Jobilla\DtoCore\Dto\v1\Documentation\Documentation;
use Jobilla\DtoCore\Dto\v1\Documentation\Tag;
use Jobilla\DtoCore\Dto\v1\Documentation\TagGroups;
use Jobilla\DtoCore\DtoAbstract;
use Illuminate\Routing\Route as LaravelRoute;

class ComposeDocumentation
{
    /**
     * @var Collection
     */
    protected $routes = [];

    /**
     * @var array
     */
    protected $definitions = [];

    /**
     * @var array
     */
    protected $tags = [];

    /**
     * @var array
     */
    protected $tagGroups = [];

    /**
     * @return array
     */
    public function get(): array
    {
        $routes = RouteFactory::fromLaravelRoutes('api/v2/');

        $tags = $routes->map(function (Route $route) {
            return Tag::from(['name' => $route->getTag()])->toArray();
        })->unique()->values();

        $tagGroups = TagGroups::from([
            'name' => 'API Endpoints',
            'tags' => $tags->pluck('name')->toArray(),
        ])->toArray();

        $paths = [];
        foreach ($this->routes as $route) {
            $paths[$route['path']][$route['method']] = $this->getMethodMetaData($route);
        }

        $documentation = Documentation::from([
            'host'        => strtr(env('APP_URL'), ['http://' => '', 'https://' => '']),
            'schemes'     => ['https'],
            'produces'    => ['application/json'],
            'info'        => [
                'version'     => 'v2',
                'title'       => 'API Documentation',
                'description' => 'API v2 endpoints documentation',
            ],
            'definitions' => $this->definitions,
            'tags'        => $this->tags,
            'x-tagGroups' => $this->tagGroups,
            'paths'       => $paths,
        ]);

        return $documentation->toArray();
    }

    /**
     * @param array $route
     *
     * @return array
     */
    private function getMethodMetaData($route): array
    {
        $reflect  = new \ReflectionMethod($route['controller'], $route['action']);
        $docBlock = $this->parseDocBlock($reflect->getDocComment());

        // Read title
        if (isset($docBlock[0]) && substr($docBlock[0], 0, 1) != '@') {
            $return['description'] = rtrim($docBlock['0'], '.') . PHP_EOL . PHP_EOL;
        }

        // Read description
        for ($index = 1; isset($docBlock[$index]); $index++) {
            if (substr($docBlock[$index], 0, 1) == '@') {
                break;
            }
            $return['description'] .= PHP_EOL . $docBlock[$index];
        }
        $return['description'] = ltrim($return['description'], "\n");

        // Read I/O annotations
        for ($index = 0; isset($docBlock[$index]); $index++) {
            if (strpos($docBlock[$index], '@input') === 0) {
                $schema = $this->getIoParameter($docBlock[$index]);
                if (isset($schema['schema']['$ref'])) {
                    $schema['in']   = 'body';
                    $schema['name'] = 'body';
                } else {
                    $schema['in']   = 'formData';
                    $schema['type'] = $schema['schema']['type'];

                    if ($schema['type'] == 'array' && !isset($schema['items'])) {
                        $schema['items'] = [
                            'type' => str_replace('_ids', '_id', $schema['name']),
                        ];
                    }
                }
                $return['parameters'][] = $schema;
            } elseif (strpos($docBlock[$index], '@output') === 0) {
                $schema = $this->getIoParameter($docBlock[$index]);
                if (isset($schema['schema']['$ref'])) {
                    $return['responses'][200] = $schema;
                } else {
                    $return['responses'][200] = [
                        'description' => 'Successful response',
                        'schema'      => [
                            'title'    => $schema['name'],
                            'type'     => $schema['schema']['type'],
                            'required' => true,
                        ],
                        'examples'    => [
                            'application/json' => [
                                'data' => [
                                    $schema['name'] => $this->getExampleTypeValue($schema['schema']['type']),
                                ],
                            ],
                        ],
                    ];
                }
            } else {
                continue;
            }
        }

        return $return;
    }

    /**
     * @param $docBlock
     *
     * @return \Illuminate\Support\Collection|static
     */
    protected function parseDocBlock($docBlock)
    {
        $docBlock = collect(explode("\n", $docBlock));
        $docBlock = $docBlock->map(function ($value) {
            $value = trim($value);
            $value = ltrim($value, '/**');
            $value = ltrim($value, '*/');
            $value = ltrim($value, '*');
            $value = trim($value);

            return $value;
        })->filter()->values();

        return $docBlock;
    }

    /**
     * @param $parameter
     *
     * @return array
     */
    private function getIoParameter($parameter): array
    {
        $parts = explode(' ', $parameter);
        $type  = substr($parts[1], 0, 1) == '\\' ? 'DTO' : $parts[1];
        $name  = $type == 'DTO' ? $parts[1] : ($parts[2] ?? 'unnamed');

        if ($type == 'DTO') {
            $this->createDtoDefinition($parts[1]);
            $docs = [
                'description' => 'Response DTO ' . $this->fqcnToName($parts[1]),
                'schema'      => [
                    '$ref' => '#/definitions/' . $this->fqcnToDefinitionName($parts[1]),
                ],
            ];
        } else {
            $docs = [
                'name'     => $name,
                'required' => true,
                'schema'   => [
                    'type' => $type,
                ],
            ];
        }

        return $docs;
    }

    private function createDtoDefinition(string $fqcn)
    {
        if (isset($this->definitions[$this->fqcnToDefinitionName($fqcn)])) {
            return null;
        }

        /** @var DtoAbstract $dto */
        $dto = new $fqcn;

        $this->definitions[$this->fqcnToDefinitionName($fqcn)] = $dto->getDocumentation();
    }

    /**
     * @param string $fqcn
     *
     * @return string
     */
    protected function fqcnToDefinitionName($fqcn)
    {
        return strtr($fqcn, ['\\' => '_']);
    }

    /**
     * @param string $fqcn
     *
     * @return string
     */
    protected function fqcnToName($fqcn)
    {
        return strtr($fqcn, ['\\' => ' -> ']);
    }

    /**
     * @param string $type
     *
     * @return float|int|string
     */
    protected function getExampleTypeValue(string $type)
    {
        switch ($type) {
            case 'integer':
                return rand(0, 100);
                break;
            case 'string':
                return 'string';
                break;
            case 'float':
                return round(mt_rand() / mt_getrandmax(), 2);
                break;
            case 'boolean':
                return true;
                break;
            case 'array':
                return [];
                break;
            default:
                return 'string';
        }
    }
}
