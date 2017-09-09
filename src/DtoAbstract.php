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
     * Populate DTO and sub-DTO-s with given data
     *
     * Only sets values for keys that are predefined in DTO.
     *
     * @param array $data
     *
     * @return $this
     * @throws \Exception
     */
    public function populateFromArray(array $data)
    {
        collect(array_intersect_key($data, $this->items))
            ->filter(function ($value) {
                return $value !== null && !empty($value);
            })
            ->map(function ($value, $key) {
                if (isset($this->subtypes[$key])) {
                    $this->populateSubtypeFromArray($key, $value);
                } else {
                    $this->items[$key] = $value;
                }
            });

        $this->validation && $this->validate();

        return $this;
    }

    /**
     * @param string $key
     * @param array  $data
     *
     * @return $this
     * @throws \Exception
     */
    protected function populateSubtypeFromArray(string $key, $data)
    {
        if (!is_array($data)) {
            throw new \Exception(
                sprintf('Dto item %s::%s in input data must be an array', static::class, $key)
            );
        }

        $subtype = $this->subtypes[$key];

        if ($this->isArrayOfSubtypes($key)) {
            foreach ($data as $subtypeData) {
                $this->items[$key][] = $this->createSubtype($subtype, $subtypeData);
            }
        } else {
            $this->items[$key] = $this->createSubtype($subtype, $data);
        }

        return $this;
    }

    /**
     * @param string           $dtoClass
     * @param Collection|Model $models
     *
     * @return array|null
     */
    protected function populateSubtype(string $dtoClass, $models)
    {
        if ($models instanceof Collection) {
            return $models->map(function (Model $model) use ($dtoClass) {
                return (new $dtoClass)->populateFromModel($model)->toArray();
            })->toArray();
        } elseif ($models instanceof Model) {
            return (new $dtoClass)->populateFromModel($models)->toArray();
        }

        return null;
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
     * turn validation off
     *
     * @return $this
     */
    public function noValidation()
    {
        $this->setValidationFlag(false);

        return $this;
    }

    /**
     * @param bool $flag
     */
    protected function setValidationFlag(bool $flag)
    {
        $this->validation = $flag;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    protected function isArrayOfSubtypes($key): bool
    {
        // will not work if dto gets updated (dtos are only meant to be populated once)
        return is_array($this->items[$key]);
    }

    /**
     * @param string $subtype
     * @param array  $data
     *
     * @return array
     */
    protected function createSubtype(string $subtype, array $data): array
    {
        $dto = new $subtype;
        $dto->setValidationFlag($this->validation);

        return $dto->populateFromArray($data)->toArray();
    }

    /**
     * @param Model[]|Collection $models
     *
     * @return Collection|DtoAbstract[]
     */
    public static function populateFromModels(Collection $models): Collection
    {
        return $models->map(function (Model $model) {
            return (new static)->populateFromModel($model);
        });
    }
}
