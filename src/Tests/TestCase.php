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
     * @return Model
     */
    abstract protected function modelProvider();

    /**
     * @test
     */
    public function populates_from_model()
    {
        $dto = (static::DTO)::from($this->modelProvider());
        $this->assertDtoFields($dto);
    }

    /**
     * @test
     */
    public function populates_from_array()
    {
        $inputArray = (static::DTO)::from($this->modelProvider())->toArray();
        $dto        = (static::DTO)::from($inputArray);

        $this->assertSame($inputArray, $dto->toArray());
        $this->assertDtoFields($dto);
    }

    /**
     * @param DtoAbstract $dto
     */
    private function assertDtoFields(DtoAbstract $dto)
    {
        $this->assertFalse($dto->validate()->fails());

        foreach ($this->filledKeys as $key) {
            $this->assertNotNull($dto[$key], "Key $key should not be null");
        }
    }
}
