<?php

namespace Jobilla\DtoCore;

use Illuminate\Contracts\Validation\Validator;

class ValidatorException extends \Exception
{
    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var array
     */
    protected $messages;

    /**
     * @return Validator
     */
    public function getValidator(): Validator
    {
        return $this->validator;
    }

    /**
     * @param Validator $validator
     *
     * @return ValidatorException
     */
    public function setValidator(Validator $validator): ValidatorException
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * @param array $messages
     */
    public function setMessages(array $messages)
    {
        $this->messages = $messages;
    }

    /**
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }
}
