<?php

/**
 * Workaround to allow throwing an exception from AspectMock while still capturing the method arguments.
 */
class sspmod_authinstagram_ArgumentCaptureException extends \Exception {
    /**
     * @var array The arguments for the method call. $this->arguments[0] is the first argument.
     */
    protected $arguments;

    /**
     * ArgumentCaptureException constructor.
     * @param string $message
     * @param array $arguments
     */
    public function __construct($message, array $arguments = []) {
        parent::__construct($message);
        $this->arguments = $arguments;
    }

    /**
     * Return the arguments that a method was called with
     * @return array
     */
    public function getArguments() {
        return $this->arguments;
    }
}