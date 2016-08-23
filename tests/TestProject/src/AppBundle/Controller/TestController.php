<?php

namespace TestProject\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use TestProject\AppBundle\Entity\TestUser;

class TestController extends Controller
{
    public function indexAction()
    {
        /** @var TestUser $user */
        $user = $this->getUser();

        return new Response("Successfully authenticated as user '{$user->getUsername()}'");
    }
}
