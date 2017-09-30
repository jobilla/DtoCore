<?php

namespace Jobilla\DtoCore\Dto\v1\Documentation;

use Jobilla\DtoCore\DtoAbstract;

class Info extends DtoAbstract
{
    /**
     * @var array
     */
    protected $items = [
        'version'     => null,
        'title'       => null,
        'description' => null,
    ];

    /**
     * @var array
     */
    protected $rules = [
        'version'     => 'string|required',
        'title'       => 'string|required',
        'description' => 'string|required',
    ];
}
