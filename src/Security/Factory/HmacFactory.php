<?php

namespace UMA\Psr7HmacBundle\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class HmacFactory implements SecurityFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $providerId = 'security.authentication.provider.hmac.'.$id;
        $container
            ->setDefinition($providerId, new DefinitionDecorator('uma.hmac.security.authentication.provider'))
            ->replaceArgument(2, new Reference($userProvider));

        $listenerId = 'security.authentication.listener.hmac.'.$id;
        $container->setDefinition(
            $listenerId, new DefinitionDecorator('uma.hmac.security.authentication.listener')
        );

        return [$providerId, $listenerId, $defaultEntryPoint];
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
        return 'hmac';
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(NodeDefinition $builder)
    {
    }
}