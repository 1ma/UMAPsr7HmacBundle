<?php

namespace TestProject\AppBundle\Security;

use Psr\Http\Message\MessageInterface;
use UMA\Psr7Hmac\Inspector\InspectorInterface;

class CustomInspector implements InspectorInterface
{
    /**
     * {@inheritdoc}
     */
    public function vet(MessageInterface $message, $verified)
    {
        if (!$verified) {
            // log failure, send email, emit event, etc.

            return false;
        }

        return true;
    }
}
