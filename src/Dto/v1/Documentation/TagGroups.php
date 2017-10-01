<?php

namespace Jobilla\DtoCore\Dto\v1\Documentation;

use Jobilla\DtoCore\DtoAbstract;

class TagGroups extends DtoAbstract
{
    /**
     * @var array
     */
    protected $items = [
        'name' => null,
        'tags' => [],
    ];

    /**
     * @var array
     */
    protected $rules = [
        'name' => 'string|required',
        'tags' => 'array|required',
    ];
}
