<?php

namespace UMA\Psr7HmacBundle\Psr7;

use Psr\Http\Message\RequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;

class RequestTransformer
{
    /**
     * @param Request $request
     *
     * @return RequestInterface
     */
    public function toPsr7(Request $request)
    {
        return (new DiactorosFactory())
            ->createRequest($request);
    }
}
