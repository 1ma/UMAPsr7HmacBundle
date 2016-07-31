<?php

namespace UMA\Psr7HmacBundle\Definition;

interface ApiClientInterface
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
