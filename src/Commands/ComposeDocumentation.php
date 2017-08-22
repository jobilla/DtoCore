<?php

namespace Jobilla\DtoCore\Commands;

use Jobilla\DtoCore\DtoAbstract;
use Illuminate\Routing\Route;

class ComposeDocumentation
{
    /**
     * @var array
     */
    protected $header = [];

    /**
     * @var array
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
        $this->createHeader();
        $this->createRoutes();
        $this->createTags();
        $this->createTagGroups();

        $structure          = $this->header;
        $structure['paths'] = collect($this->routes)->sort()->map(function ($route) {
            return [$route['method'] => $this->getMethodMetaData($route)];
        });

        $structure['definitions'] = $this->definitions;
        $structure['tags']        = $this->tags;
        $structure['x-tagGroups'] = $this->tagGroups;

        return $structure;
    }

    protected function createHeader()
    {
        $this->header = [
            'swagger'     => '2.0',
            'schemes'     => ['https'],
            'produces'    => ['application/json'],
            'host'        => strtr(env('APP_URL'), ['http://' => '', 'https://' => '']),
            'info'        => [
                'version'     => 'v1',
                'title'       => 'Dynamely API',
                'description' => 'Dynamely API documentation and endpoint reference'
            ],
            'tags'        => [],
            'x-tagGroups' => [],
            'paths'       => [],
            'definitions' => []
        ];
    }

    protected function createRoutes()
    {
        $this->routes = collect(\Route::getRoutes())->mapWithKeys(function (Route $route) {
            if (strpos($route->getPath(), 'api/v1/') !== 0) {
                return null;
            }

            if ($route->getPath() == 'api/v1/documentation') {
                return null;
            }

            $path     = '/' . $route->getPath();
            $methods  = $route->getMethods();
            $methods  = collect($methods)->diff(['HEAD']);
            $method   = strtolower($methods->first());
            $action   = substr($route->getActionName(), strpos($route->getActionName(), '@') + 1);
            $category = substr($path, strlen('/api/v1/'));

            strpos($category, '/') && $category = substr($category, 0, strpos($category, '/'));

            return [
                $path => [
                    'path'       => $path,
                    'method'     => $method,
                    'route'      => $route->getPath(),
                    'controller' => get_class($route->getController()),
                    'action'     => $action,
                    'category'   => $category
                ]
            ];
        })->filter()->sort()->toArray();
    }

    protected function createTags()
    {
        $tags = [];

        foreach ($this->routes as $path => $route) {
            $category = $route['category'];

            $tags[$category] = [
                'name' => $category
            ];
        }

        // Avoids having duplicates
        $this->tags = array_values($tags);
    }

    protected function createTagGroups()
    {
        $this->tagGroups[] = [
            'name' => 'API Endpoints',
            'tags' => collect($this->tags)->pluck('name')
        ];
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

        $return = [
            'tags'        => [$route['category']],
            'summary'     => $route['path'],
            'description' => null,
            'parameters'  => [],
            'responses'   => []
        ];

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
                }
                $return['parameters'][] = $schema;
            } elseif (strpos($docBlock[$index], '@output') === 0) {
                $schema = $this->getIoParameter($docBlock[$index]);
                if (isset($schema['schema']['$ref'])) {
                    $return['response'][200] = $schema;
                } else {
                    $return['responses'][200] = [
                        'description' => 'Successful response',
                        'schema'      => [
                            'title'    => $schema['name'],
                            'type'     => $schema['schema']['type'],
                            'required' => true
                        ],
                        'examples'    => [
                            'application/json' => [
                                'data' => [
                                    $schema['name'] => $this->getExampleTypeValue($schema['schema']['type'])
                                ]
                            ]
                        ]
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
        $name  = $type == 'DTO' ? $parts[1] : $parts[2];

        if ($type == 'DTO') {
            $this->createDtoDefinition($parts[1]);
            $docs = [
                'description' => 'Response DTO ' . $this->fqcnToName($parts[1]),
                'schema'      => [
                    '$ref' => '#/definitions/' . $this->fqcnToDefinitionName($parts[1])
                ]
            ];
        } else {
            $docs = [
                'name'     => $name,
                'required' => true,
                'schema'   => [
                    'type' => $type
                ]
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
            default:
                return 'string';
        }
    }
}
