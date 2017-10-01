<?php

namespace Jobilla\DtoCore\Documentation;

class Path
{
    /**
     * @var Route
     */
    protected $route;

    /**
     * @param Route $route
     */
    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    public function getDocBlock()
    {
        
    }
}
