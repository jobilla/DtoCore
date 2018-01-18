<?php

use Illuminate\Container\Container;
use Illuminate\Validation\Validator;

class DtoTest extends \PHPUnit\Framework\TestCase
{
    public function tearDown()
    {
        \Illuminate\Support\Facades\Facade::clearResolvedInstances();
    }

    public function test_it_hydrates_a_dto()
    {
        \Illuminate\Support\Facades\Validator::shouldReceive('make')->andReturn($v = Mockery::mock(Validator::class));
        $v->shouldReceive('fails')->andReturn(false);

        $user = User::from(['name' => 'Leo', 'email' => 'leo@jobilla.com']);

        $this->assertEquals('Leo', $user['name']);
        $this->assertEquals('leo@jobilla.com', $user['email']);
    }

    /**
     * @expectedException \Jobilla\DtoCore\ValidatorException
     */
    public function test_it_throws_an_exception_when_validation_fails()
    {
        \Illuminate\Support\Facades\Validator::shouldReceive('make')->andReturn($v = Mockery::mock(Validator::class));
        $v->shouldReceive('fails')->andReturn(true);
        $v->shouldReceive('getMessageBag->toJson')->andReturn('{"email": "required"}');
        $v->shouldReceive('getMessageBag->toArray')->andReturn(['email' => 'required']);

        User::from(['name' => 'Leo']);
    }

    public function test_it_skips_validation_when_requested()
    {
        $user = UserWithoutValidation::from(['name' => 'Leo']);

        $this->assertEquals('Leo', $user['name']);
        $this->assertNull($user['email']);
    }

    public function test_it_hydrates_subtypes()
    {
        \Illuminate\Support\Facades\Validator::shouldReceive('make')->andReturn($v = Mockery::mock(Validator::class));
        $v->shouldReceive('fails')->andReturn(false);

        $user = UserWithAddress::from([
            'name' => 'Leo',
            'email' => 'leo@jobilla.com',
            'address' => [
                'line1' => 'Hermannin rantatie 12',
                'zip' => '00580',
                'city' => 'Helsinki',
                'country' => 'Finland',
            ]
        ]);

        $this->assertEquals('Hermannin rantatie 12', $user['address']['line1']);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage A valid FQCN must be specified to use implicit route binding with DTOs
     */
    public function test_it_throws_an_exception_when_no_model_is_set_and_route_binding_is_attempted()
    {
        $user = new User;

        $user->resolveRouteBinding(3);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage A valid FQCN must be specified to use implicit route binding with DTOs
     */
    public function test_it_throws_an_exception_when_a_nonexistent_model_is_set_and_route_binding_is_attempted()
    {
        $user = new UserWithInvalidModel;

        $user->resolveRouteBinding(3);
    }

    public function test_it_proxies_route_binding_resolution_to_the_attached_model()
    {
        \Illuminate\Support\Facades\Validator::shouldReceive('make')->andReturn($v = Mockery::mock(Validator::class));
        $v->shouldReceive('fails')->andReturn(false);

        $user = new UserWithModel();
        $user = $user->resolveRouteBinding(3);

        $this->assertEquals('Leo', $user['name']);
    }
}

class User extends \Jobilla\DtoCore\DtoAbstract {
    protected $rules = [
        'name' => 'required|string',
        'email' => 'required|email',
    ];

    protected $items = [
        'name' => null,
        'email' => null,
        'address_id' => null,
    ];
}

class UserWithAddress extends \Jobilla\DtoCore\DtoAbstract {
    protected $rules = [
        'name' => 'required|string',
        'email' => 'required|email',
    ];

    protected $items = [
        'name' => null,
        'email' => null,
        'address' => null,
    ];

    protected $subtypes = [
        'address' => Address::class,
    ];
}

class UserWithModel extends User {
    protected $model = Model::class;
}

class UserWithInvalidModel extends User {
    protected $model = 'SomeInvalidModel';
}

class Model {
    public function resolveRouteBinding($value)
    {
        return ['name' => 'Leo'];
    }
}

class UserWithoutValidation extends User {
    protected $validation = false;
}

class Address extends \Jobilla\DtoCore\DtoAbstract {
    protected $rules = [
        'line1' => 'required|string',
        'line2' => 'string',
        'zip'   => 'required',
        'city' => 'string',
        'country' => 'required|string',
    ];

    protected $items = [
        'line1' => null,
        'line2' => null,
        'city' => null,
        'zip'   => null,
        'country' => null,
    ];
}
