<?php

namespace Jobilla\DtoCore;

use Exception;
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
     * The FQCN of the model related to this DTO. Used for implicit route binding.
     *
     * @var string
     */
    protected $model;

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
     * Populate and validate a DTO and sub-DTO-s from data array
     *
     * - Can be used in controllers, to fill from Request::all()
     * - Only sets values for keys that are predefined in DTO
     *
     * @param array $data
     *
     * @return $this
     * @throws Exception
     */
    public function populateFromArray(array $data): DtoAbstract
    {
        collect(array_intersect_key($data, $this->toArray()))
            ->filter(function ($value) {
                return !in_array($value, [null, ''], true);
            })
            ->map(function ($value, $key) {
                if (is_array($value) && $this->getSubtype($key)) {
                    $value = $this->populateSubtypeFromArray($key, $value);
                }

                $this[$key] = $value;
            });

        $this->validation && $this->validate();

        return $this;
    }

    /**
     * Populate subtype(s) from array, and return the toArray() of subtype(s)
     *
     * Subtypes in DTO-s can be defined as an array of subtype objects, or as one subtype object.
     *
     * This method detects if DTO subtype is an array of subtypes, or just one subtype.
     * After populating the subtype(s) from array(s), the toArray() of one subtype DTO is returned,
     * or an array of subtype DTO-s toArray()-s.
     *
     * @param string $key
     * @param array  $value
     *
     * @return array
     */
    private function populateSubtypeFromArray(string $key, array $value): array
    {
        if (is_array($this[$key])) {
            return collect($value)->map(function (array $values) use ($key) {
                return (new $this->subtypes[$key])->populateFromArray($values)->toArray();
            })->toArray();
        }

        return (new $this->subtypes[$key])->populateFromArray($value)->toArray();
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
            return $source->map(function (Model $model) {
                return (new static)->populateFromModel($model);
            });
        } elseif ($source instanceof Model) {
            return (new static)->populateFromModel($source);
        } elseif (is_array($source)) {
            return (new static)->populateFromArray($source);
        }

        return collect();
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
     * Throw validation exception
     *
     * Include list of failures for each field, in machine-parseable format.
     *
     * @param Validator $validator
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

    /**
     * @param bool $validation
     *
     * @return DtoAbstract
     */
    public function setValidation(bool $validation): DtoAbstract
    {
        $this->validation = $validation;

        return $this;
    }

    /**
     * Resolve a route binding by delegating to the underlying resource.
     *
     * @param $value
     * @return $this[]|Collection|DtoAbstract
     * @throws Exception
     */
    public function ResolveRouteBinding($value)
    {
        if (! ($this->model && class_exists($this->model))) {
            throw new Exception('A valid FQCN must be specified to use implicit route binding with DTOs');
        }

        return static::from((new $this->model)->resolveRouteBinding($value));
    }
}
