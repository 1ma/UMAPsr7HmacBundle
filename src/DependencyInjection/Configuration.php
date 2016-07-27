<?php

namespace UMA\Psr7HmacBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_APIKEY_HEADER = 'X-ApiKey';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        return (new TreeBuilder())
            ->root('uma_psr7hmac')
                ->children()
                    ->scalarNode('apikey_header')->defaultValue(static::DEFAULT_APIKEY_HEADER)->end()
                ->end()
            ->end();
    }
}
