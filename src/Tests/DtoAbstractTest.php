<?php
namespace Jobilla\DtoCore\Tests;

use App\Dto\ValidatorException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @coversDefaultClass \App\Dto\DtoAbstract
 */
class DtoAbstractTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     * @covers ::__construct
     */
    public function new_instance_has_preset_structure()
    {
        $testDto = new TestDto;

        $items = [
            'myKey'        => null,
            'subType'      => null,
            'subTypeArray' => []
        ];

        $this->assertSame($items, $testDto->toArray());
    }

    /**
     * @test
     * @covers ::__construct
     */
    public function dto_uses_protected_items_property_for_structure()
    {
        $testDto = new TestDto(['differentStruct' => 123]);

        $items = [
            'myKey'        => null,
            'subType'      => null,
            'subTypeArray' => []
        ];

        $this->assertSame($items, $testDto->toArray());
    }

    /**
     * @test
     * @covers ::fromArray
     * @covers ::populateSubtypeFromArray
     */
    public function populate_from_array_sets_correct_subtype()
    {
        $testDto = new TestDto;

        $sub1 = new TestDto;
        $sub1->populateFromArray(['myKey' => 'sub 1']);

        $sub2 = new TestDto;
        $sub2->populateFromArray(['myKey' => 'sub 2']);

        $sub3 = new TestDto;
        $sub3->populateFromArray(['myKey' => 'sub 3']);

        $items = [
            'myKey'        => 'abc',
            'subType'      => $sub1->toArray(),
            'subTypeArray' => [$sub2->toArray(), $sub3->toArray()]
        ];

        $testDto->populateFromArray($items);

        $result = $testDto->toArray();

        $this->assertSame('abc', $result['myKey']);
        $this->assertSame('sub 1', $result['subType']['myKey']);
        $this->assertSame('sub 2', $result['subTypeArray'][0]['myKey']);
        $this->assertSame('sub 3', $result['subTypeArray'][1]['myKey']);
    }

    /**
     * @test
     * @covers ::populateSubtypeFromArray
     * @expectedException \Exception
     */
    public function populate_from_array_throws_exception_if_subtype_not_array()
    {
        $testDto = new TestDto;
        $testDto->populateFromArray(['myKey' => 'sub 1', 'subType' => 'abc']);
    }

    /**
     * @test
     * @covers ::populateSubtypeFromArray
     * @expectedException \Exception
     */
    public function populate_from_array_throws_exception_if_array_of_subtypes_not_array()
    {
        $testDto = new TestDto;
        $testDto->populateFromArray(['myKey' => 'sub 1', 'subTypeArray' => 'abc']);
    }

    /**
     * @test
     * @covers ::validate
     */
    public function validation_validates_items()
    {
        $testDto = new TestDto;
        $testDto->populateFromArray(['myKey' => 'some string']);

        $this->assertFalse($testDto->validate()->fails());
    }

    /**
     * @test
     * @covers ::validate
     * @expectedException \Exception
     */
    public function validation_throws_exception_for_invalid_items()
    {
        $testDto = new TestDto;
        $testDto->populateFromArray(['myKey' => 234]);
    }

    /**
     * @test
     * @covers ::validate
     * @covers \App\Dto\ValidatorException::getValidator
     * @covers \App\Dto\ValidatorException::setValidator
     * @expectedException \Exception
     */
    public function can_get_message_bag_if_validation_exception_thrown()
    {
        $testDto = new TestDto;

        try {
            $testDto->populateFromArray(['myKey' => 234]);
        } catch (ValidatorException $exception) {
            $this->assertInstanceOf(Validator::class, $exception->getValidator());
            $this->assertNotEmpty($exception->getValidator()->getMessageBag());
            throw $exception;
        }
    }
}
