<?php

namespace Jobilla\DtoCore\Dto\v1\Documentation;

use Jobilla\DtoCore\DtoAbstract;

class Documentation extends DtoAbstract
{
    /**
     * @var array
     */
    protected $items = [
        'swagger'     => '2.0',
        'schemes'     => ['https'],
        'produces'    => ['application/json'],
        'host'        => null,
        'info'        => null,
        'tags'        => [],
        'x-tagGroups' => [],
        'paths'       => [],
        'definitions' => [],
    ];

    /**
     * @var array
     */
    protected $rules = [
        'swagger'     => 'string|required|in:2.0',
        'schemes'     => 'array|required',
        'produces'    => 'array|required',
        'host'        => 'string|required',
        'info'        => 'array|required',
        'tags'        => 'array|required',
        'x-tagGroups' => 'array|required',
        'paths'       => 'array|required',
        'definitions' => 'array|required',
    ];
}
