<?php

namespace Jobilla\DtoCore\Dto\v1\Documentation;

use Jobilla\DtoCore\DtoAbstract;

class Path extends DtoAbstract
{
    /**
     * @var array
     */
    protected $items = [
        'tags'        => [],
        'summary'     => null,
        'description' => null,
        'parameters'  => [],
        'responses'   => [],
    ];

    /**
     * @var array
     */
    protected $rules = [
        'tags'        => 'array|required',
        'summary'     => 'string|required',
        'description' => 'string|required',
        'parameters'  => 'array|required',
        'responses'   => 'array|requried',
    ];
}
