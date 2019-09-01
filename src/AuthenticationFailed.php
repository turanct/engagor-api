<?php

namespace Engagor;

use Exception;

final class AuthenticationFailed extends Exception
{
    private $response;

    public static function fromResponseArray(array $response)
    {
        $exception = new static(
            $response['error_description']
        );

        $exception->response = $response;

        return $exception;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function __toString()
    {
        $string = parent::__toString();
        $string .= "\n\n";
        $string .= json_encode($this->response);

        return $string;
    }
}
