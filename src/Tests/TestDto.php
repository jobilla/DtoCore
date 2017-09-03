<?php
namespace Jobilla\DtoCore\Tests;

use App\Dto\DtoAbstract;

class TestDto extends DtoAbstract
{
    /**
     * @var array
     */
    protected $items = [
        'myKey'        => null,
        'subType'      => null,
        'subTypeArray' => []
    ];

    /**
     * @var array
     */
    protected $subtypes = [
        'subType'      => self::class,
        'subTypeArray' => self::class
    ];

    /**
     * @var array
     */
    protected $rules = [
        'myKey' => 'string'
    ];

    /**
     * @param object $myObject
     *
     * @return $this
     */
    public function populateFromModel($myObject)
    {
        $this->items['mykey']        = $myObject->mykey;
        $this->items['subType']      = $myObject->subType;
        $this->items['subTypeArray'] = $myObject->subTypeArray;

        return $this;
    }
}
