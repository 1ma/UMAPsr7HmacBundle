<?php

namespace UMA\Psr7HmacBundle\Diactoros;

use Psr\Http\Message\RequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;

class Psr7Transformer
{
    /**
     * @param Request $request
     *
     * @return RequestInterface
     */
    public function transform(Request $request)
    {
        return (new DiactorosFactory())
            ->createRequest($request);
    }
}
