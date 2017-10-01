<?php

namespace Jobilla\DtoCore\Documentation;

use Illuminate\Routing\Route as LaravelRoute;

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
        return get_class($route->getController());
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
}
