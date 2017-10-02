<?php

namespace Jobilla\DtoCore\Documentation;

use Jobilla\DtoCore\DtoAbstract;

class Definition
{
    /**
     * @var DtoAbstract
     */
    protected $classInstance;

    /**
     * @param string $className
     *
     * @throws \Exception
     */
    public function __construct(string $className)
    {
        $this->classInstance = new $className;
    }

    /**
     * @return array
     */
    public function getStructure(): array
    {
        return $this->classInstance->getDocumentation();
    }

    /**
     * @return string
     */
    public function definitionId(): string
    {
        return strtr(get_class($this->classInstance), ['\\' => '_']);
    }
}
