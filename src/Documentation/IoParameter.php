<?php

namespace Jobilla\DtoCore\Documentation;

use phpDocumentor\Reflection\DocBlock\Tags\Generic;

class IoParameter
{
    /**
     * The full string after `@input ` and `@output `
     *
     * @var string
     */
    protected $tagValue;

    /**
     * @param string $tagValue
     */
    public function __construct(string $tagValue)
    {
        $this->tagValue = $tagValue;
    }

    /**
     * @return array
     */
    public function getStructure(): array
    {
        if (strpos($this->tagValue, '\\') !== false) {
            return $this->dtoStructure($this->tagValue);
        }

        return $this->scalarStructure($this->tagValue);
    }

    /**
     * @param $value
     *
     * @return array
     */
    protected function dtoStructure(): array
    {
        return [
            'description' => 'DTO ' . $this->definitionTitle(),
            'in'          => 'body',
            'name'        => 'body',
            'schema'      => ['$ref' => '#/definitions/' . $this->definitionId()],
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function scalarStructure()
    {
        list($type, $fieldName) = $this->explodeScalarTagValue();

        $parameters = [
            'name'        => $fieldName,
            'required'    => true,
            'description' => $fieldName,
            'in'          => 'formData',
            'type'        => $type,
            'schema'      => [
                'title'    => $fieldName,
                'type'     => $type,
                'required' => true,
            ],
            'examples'    => [
                'application/json' => [
                    'data' => [
                        $fieldName => $this->getExampleTypeValue($type),
                    ],
                ],
            ],
        ];

        if ($type === 'array') {
            $parameter['items'] = [
                'type' => str_replace('_ids', '_id', $fieldName),
            ];
        }

        return $parameters;
    }

    /**
     * @return string
     */
    public function definitionId(): string
    {
        return strtr(trim($this->tagValue, '\\'), ['\\' => '_']);
    }

    /**
     * @return string
     */
    protected function definitionTitle(): string
    {
        return strtr($this->tagValue, ['\\' => ' -> ']);
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function explodeScalarTagValue(): array
    {
        $parts = explode(' ', $this->tagValue);

        if (count($parts) !== 2) {
            throw new \Exception('API Docs specification is invalid for value: ' . $this->tagValue);
        }

        return $parts;
    }

    /**
     * @param string $type
     *
     * @return float|int|string
     */
    protected function getExampleTypeValue(string $type)
    {
        switch ($type) {
            case 'integer':
                return rand(0, 100);
                break;
            case 'string':
                return 'string';
                break;
            case 'float':
                return round(mt_rand() / mt_getrandmax(), 2);
                break;
            case 'boolean':
                return true;
                break;
            case 'array':
                return [];
                break;
            default:
                return 'string';
        }
    }
}
