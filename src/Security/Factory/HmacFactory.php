<?php

namespace UMA\Psr7HmacBundle\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class HmacFactory implements SecurityFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        // TODO: Implement create() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition()
    {
        return 'http';
    }

    /**
     * {@inheritdoc}
     */
    public function getKey()
    {
        return 'psr7_hmac';
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(NodeDefinition $builder)
    {
    }
}
