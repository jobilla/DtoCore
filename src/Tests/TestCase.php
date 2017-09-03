<?php

namespace Jobilla\DtoCore\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Jobilla\DtoCore\DtoAbstract;
use Tests\TestCase as RootTestCase;

abstract class TestCase extends RootTestCase
{
    use DatabaseTransactions;

    /**
     * @var array
     */
    protected $filledKeys = [];

    /**
     * @return DtoAbstract
     */
    abstract protected function dtoProvider(): DtoAbstract;

    /**
     * @return Model
     */
    abstract protected function modelProvider();

    /**
     * @test
     */
    public function populates_from_model()
    {
        $dto = $this->dtoProvider();
        $dto->populateFromModel($this->modelProvider());

        $this->assertFalse($dto->validate()->fails());

        $array = $dto->toArray();

        foreach ($this->filledKeys as $key) {
            $this->assertNotNull($array[$key], "Key $key should not be null");
        }
    }

    /**
     * @test
     */
    public function populates_from_array()
    {
        $dto = $this->dtoProvider();
        $dto->populateFromModel($this->modelProvider());
        $array = $dto->toArray();

        $dto = $this->dtoProvider();
        $dto->populateFromArray($array);

        $this->assertFalse($dto->validate()->fails());
        $this->assertSame($array, $dto->toArray());

        foreach ($this->filledKeys as $key) {
            $this->assertNotNull($array[$key]);
        }
    }
}
