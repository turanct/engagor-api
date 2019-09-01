<?php

namespace Engagor;

use Exception;
use Psr\Http\Message\RequestInterface;

final class ApiCallFailed extends Exception
{
    private $request;

    public static function forRequest(RequestInterface $request, Exception $e)
    {
        $exception = new static(
            'Api call failed',
            0,
            $e
        );

        $exception->request = $request;

        return $exception;
    }

    public function getRequest()
    {
        return $this->request;
    }
}
