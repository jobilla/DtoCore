<?php

namespace Jobilla\DtoCore;

trait DtoDocumentationTrait
{
    /**
     * @return array
     */
    public function getDocumentation(): array
    {
        $return = [
            'type'       => 'object',
            'properties' => []
        ];

        foreach ($this->items as $key => $value) {
            if (isset($this->subtypes[$key])) {
                /** @var DtoAbstract $subDto */
                $subDto  = new $this->subtypes[$key];
                $subDocs = $subDto->getDocumentation();

                if (is_array($value)) {
                    $return['properties'][$key] = [
                        'type'  => 'array',
                        'items' => $subDocs
                    ];
                } else {
                    $return['properties'][$key] = $subDocs;
                }
            } else {
                if (isset($this->rules[$key])) {
                    $rule      = $this->rules[$key];
                    $ruleItems = explode('|', $rule);

                    switch ($ruleItems[0]) {
                        case 'int':
                            $type = 'integer';
                            break;
                        case 'numeric':
                            $type = 'number';
                            break;
                        case 'bool':
                            $type = 'boolean';
                            break;
                        case 'string':
                            $type = 'string';
                            break;
                        case 'date':
                            $type = 'date';
                            break;
                        case 'array':
                            $type = 'array';
                            break;
                        default:
                            $type = 'string';
                    }

                    $return['properties'][$key] = [
                        'type'        => $type,
                        'required'    => in_array('required', $ruleItems),
                        'description' => 'Validation rules: ' . $rule
                    ];
                }
            }
        }

        return $return;
    }
}
