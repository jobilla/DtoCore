<?php

namespace Jobilla\DtoCore\Dto\v1\Documentation;

use Jobilla\DtoCore\DtoAbstract;

class Tag extends DtoAbstract
{
    /**
     * @var array
     */
    protected $items = [
        'name' => null,
    ];

    /**
     * @var array
     */
    protected $rules = [
        'name' => 'string|required',
    ];
}
