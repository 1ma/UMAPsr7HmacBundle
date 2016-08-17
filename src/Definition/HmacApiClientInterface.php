<?php

namespace UMA\Psr7HmacBundle\Definition;

interface HmacApiClientInterface
{
    /**
     * @return string
     */
    public function getApiKey();

    /**
     * @return string
     */
    public function getSharedSecret();
}
