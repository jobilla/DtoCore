<?php

namespace Jobilla\DtoCore\Documentation;

use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Collection;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;

class Route
{
    /**
     * The Laravel Route instance
     *
     * @var LaravelRoute
     */
    protected $laravelRoute;

    /**
     * API URI path prefix
     *
     * @var string
     */
    protected $prefix;

    /**
     * @var DocBlock
     */
    protected $docBlock;

    /**
     * @param LaravelRoute $laravelRoute
     */
    public function __construct(LaravelRoute $laravelRoute, string $prefix)
    {
        $this->laravelRoute = $laravelRoute;
        $this->prefix       = $prefix;
    }

    /**
     * Get API URI path, prefixed with `/`
     *
     * @return string
     */
    public function getPath(): string
    {
        return '/' . $this->laravelRoute->getPath();
    }

    /**
     * Return HTTP method
     *
     * Return one of: get, post, put, patch, delete, etc.
     * Ignore: head
     *
     * @return string
     */
    public function getHttpMethod(): string
    {
        $methods = $this->laravelRoute->getMethods();
        $methods = collect($methods)->diff(['HEAD']);

        return strtolower($methods->first());
    }

    /**
     * Return action method name
     *
     * @return string
     */
    public function getAction(): string
    {
        return substr($this->laravelRoute->getActionName(), strpos($this->laravelRoute->getActionName(), '@') + 1);
    }

    /**
     * Get this route tag for docs menu tags
     *
     * @return string
     */
    public function getTag(): string
    {
        $tag = substr($this->getPath(), strlen('/' . $this->prefix));

        if (strpos($tag, '/')) {
            $tag = substr($tag, 0, strpos($tag, '/'));
        }

        return $tag;
    }

    /**
     * Get FQCN of controller class
     *
     * @return string
     */
    public function getControllerClass(): string
    {
        return get_class($this->laravelRoute->getController());
    }

    /**
     * @return LaravelRoute
     */
    public function getLaravelRoute(): LaravelRoute
    {
        return $this->laravelRoute;
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Initialize the DocBlock using PHPDocumentor
     */
    public function initalizeDocBlock()
    {
        $reflect        = new \ReflectionMethod($this->getControllerClass(), $this->getAction());
        $docBlockString = $reflect->getDocComment();

        $factory        = DocBlockFactory::createInstance();
        $this->docBlock = $factory->create($docBlockString);
    }

    /**
     * @param string $name
     *
     * @return array
     */
    protected function getTagValuesByName(string $name): array
    {
        !$this->docBlock && $this->initalizeDocBlock();

        return collect($this->docBlock->getTagsByName($name))
            ->map(function (DocBlock\Tags\Generic $tag) {
                return $tag->getDescription()->render();
            })->toArray();
    }

    /**
     * @return array|string[]
     */
    public function getInputs(): array
    {
        return $this->getTagValuesByName('input');
    }

    /**
     * @return array|string[]
     */
    public function getOutputs(): array
    {
        return $this->getTagValuesByName('output');
    }

    /**
     * @return array|string[]
     */
    public function getIoTags(): array
    {
        return array_merge($this->getInputs(), $this->getOutputs());
    }

    /**
     * @return array|string[]
     */
    public function getIoClasses(): array
    {
        return collect($this->getIoTags())
            ->filter(function (string $tag) {
                return strpos($tag, '\\') !== false;
            })
            ->filter()
            ->toArray();
    }
}
