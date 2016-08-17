<?php

namespace UMA\Psr7HmacBundle\Definition;

use Symfony\Component\Security\Core\User\UserInterface;

interface HmacApiUserInterface extends HmacApiClientInterface, UserInterface
{
}
