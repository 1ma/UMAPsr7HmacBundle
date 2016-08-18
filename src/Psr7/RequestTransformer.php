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
        // Prevent DiactorosFactory::createRequest from locking
        // the content out from subsequent Request client code. This hack
        // is needed until https://github.com/symfony/symfony/pull/19549
        // finds its way into a future Symfony release.
        $request->getContent();

        return (new DiactorosFactory())
            ->createRequest($request);
    }
}
