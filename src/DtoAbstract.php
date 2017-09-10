<?php

namespace Jobilla\DtoCore;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * DTO Abstract class
 *
 * Concrete DTO classes should extend this class.
 *
 * Minimal DTO class:
 *
 * ```
 * class CompanyDto extends DtoAbstract {
 *   protected $items = ['id' => null, 'name' => null];
 *   protected $rules = ['id' => 'int|nullable|in:companies', 'name' => 'string|nullable|max:100'];
 * }
 * ```
 */
abstract class DtoAbstract extends Collection
{
    use DtoDocumentationTrait;

    /**
     * DTO-s fields default date and datetime formats
     */
    const DATE_FORMAT     = 'Y-m-d';
    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * DTO fields
     *
     * Provide default field values in concrete class items array.
     *
     *  - For a subtype, the field value must be `null`.
     *  - For an array of subtypes, the field value must be `[]`.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Field-to-subtype map
     *
     * ```
     * [
     *   'company' => CompanyDto::class,
     *   'articles' => ArticleDto::class,
     * ]
     * ```
     *
     * @var array
     */
    protected $subtypes = [];

    /**
     * Laravel Validation compatible rules
     *
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
     * Get subtype class
     *
     * Return null if subtype is not defined.
     *
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
     * Validate DTO by rules defined in $rules
     *
     * @return Validator
     * @throws ValidatorException
     */
    public function validate(): Validator
    {
        $validator = \Validator::make($this->items, $this->rules);

        if ($validator->fails()) {
            $this->throwValidationException($validator);
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

        $dto->validate();

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

        $dto->validate();

        return $dto;
    }

    /**
     * DTO factory method
     *
     * Create new DTO instances, and populate them depending on $source type.
     *
     * Accepts:
     *
     *   - Collection of Model instances - return a Collection of populated DTO instances
     *   - Model instance - return and populate a DTO instance
     *   - Array of data - return and populate a DTO instance
     *
     * Provide default return value, an empty Collection instance, to accommodate
     * for sub-DTO's generation, with `$this['company'] = CompanyDto::from($page->companies)->toArray();`.
     * In this case, the `from` call always returns an instance of Collection (Collection of DTO-s,
     * one DTO, or an empty Collection), which can always be converted to an array for the subtype value.
     *
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

    /**
     * Throw validation exception
     *
     * Include list of failures for each field, in machine-parseable format.
     *
     * @param $validator
     *
     * @throws ValidatorException
     */
    protected function throwValidationException(Validator $validator): void
    {
        $message = sprintf(
            'DTO Validation failed for %s. Messages: %s',
            get_called_class(),
            $validator->getMessageBag()->toJson()
        );

        $exception = new ValidatorException($message);
        $exception->setValidator($validator);
        $exception->setTitle(sprintf('DTO Validation failed for %s', get_called_class()));
        $exception->setMessages($validator->getMessageBag()->toArray());

        throw $exception;
    }
}
