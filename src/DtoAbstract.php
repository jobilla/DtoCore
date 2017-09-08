<?php

namespace Jobilla\DtoCore;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class DtoAbstract extends Collection
{
    use DtoDocumentationTrait;

    const DATE_FORMAT     = 'Y-m-d';
    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * Define keys of sub-DTO-s here
     *
     * @var array
     */
    protected $subtypes = [];

    /**
     * @var array
     */
    protected $rules = [];

    /**
     * Turn validation on/off
     *
     * @var bool
     */
    protected $validation = true;

    /**
     * @param array $items - NOT used
     */
    public function __construct($items = [])
    {
        parent::__construct($this->items);
    }

    /**
     * @param string $field
     *
     * @return string|bool
     */
    public function getSubtype(string $field)
    {
        return $this->subtypes[$field] ?? false;
    }

    /**
     * Turn validation off
     *
     * @return $this
     */
    public function noValidation()
    {
        $this->validation = false;

        return $this;
    }

    /**
     * @return bool
     */
    public function isValidation(): bool
    {
        return $this->validation;
    }

    /**
     * Validate DTO by rules defined in $rules
     *
     * @return Validator
     * @throws ValidatorException
     */
    public function validate(): Validator
    {
        $validator = \Validator::make($this->items, $this->rules);

        if ($validator->fails()) {
            $exception = new ValidatorException(sprintf(
                'DTO Validation failed for %s. Messages: %s',
                get_called_class(),
                $validator->getMessageBag()->toJson()
            ));
            $exception->setValidator($validator);
            $exception->setTitle(sprintf('DTO Validation failed for %s', get_called_class()));
            $exception->setMessages($validator->getMessageBag()->toArray());
            throw $exception;
        }

        return $validator;
    }

    /**
     * @param Model $model
     *
     * @return mixed
     */
    public static function fromModel(Model $model): DtoAbstract
    {
        /** @var DtoAbstract $dto */
        $dto = (new static)->populateFromModel($model);

        $dto->isValidation() && $dto->validate();

        return $dto;
    }

    /**
     * @param Model[]|Collection $models
     *
     * @return Collection|$self[]
     */
    public static function fromModels(Collection $models): Collection
    {
        return $models->map(function (Model $model) {
            return static::fromModel($model);
        });
    }

    /**
     * Populate DTO and sub-DTO-s from data array
     *
     * - Can be used in controllers, to fill from Request::all()
     * - Only sets values for keys that are predefined in DTO
     *
     * @param array $data
     *
     * @return $this
     * @throws \Exception
     */
    public static function fromArray(array $data): DtoAbstract
    {
        $dto = new static;

        collect(array_intersect_key($data, $dto->toArray()))
            ->filter(function ($value) {
                return $value !== null && !empty($value);
            })
            ->map(function ($value, $key) use ($dto) {
                if (is_array($value) && $dto->getSubtype($key)) {
                    $value = $dto->getSubtype($key)::from($value)->toArray();
                }

                $dto[$key] = $value;
            });

        $dto->isValidation() && $dto->validate();

        return $dto;
    }

    /**
     * @param Model|Collection|Model[]|array $source
     *
     * @return $this|Collection|$this[]
     */
    public static function from($source)
    {
        if ($source instanceof Collection) {
            return static::fromModels($source);
        } elseif ($source instanceof Model) {
            return static::fromModel($source);
        } elseif (is_array($source)) {
            return static::fromArray($source);
        }

        return collect();
    }
}
