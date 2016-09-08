<?php

namespace UMA\Psr7HmacBundle\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;
use UMA\Psr7Hmac\Verifier;

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
        $container
            ->setDefinition($listenerId, new DefinitionDecorator('uma.hmac.security.authentication.listener'))
            ->replaceArgument(0, $config['apikey_header'])
            ->replaceArgument(3, new Reference($defaultEntryPoint, ContainerInterface::NULL_ON_INVALID_REFERENCE));

        if (isset($config['inspector_id'])) {
            $verifierId = 'security.authentication.verifier.hmac.'.$id;
            $inspectorRef = new Reference($config['inspector_id']);

            $container
                ->setDefinition($verifierId, new Definition(Verifier::class, [$inspectorRef]));

            $container
                ->getDefinition($providerId)
                ->replaceArgument(3, new Reference($verifierId));
        }

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
     *
     * @param ArrayNodeDefinition $builder
     */
    public function addConfiguration(NodeDefinition $builder)
    {
        $builder
            ->children()
                ->scalarNode('apikey_header')->defaultValue('Api-Key')->end()
                ->scalarNode('inspector_id')->end()
            ->end();
    }
}
