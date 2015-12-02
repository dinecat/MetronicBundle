<?php

/**
 * This file is part of the DinecatThemeBundle package.
 * @copyright   2015 UAB Dinecat, http://dinecat.com/
 * @license     http://dinecat.com/licenses/mit MIT License
 * @link        https://github.com/dinecat/ThemeBundle
 */

namespace Dinecat\ThemeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Bundle configuration.
 * @package DinecatThemeBundle\Config
 * @author  Mykola Zyk <relo.san.pub@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder;
        $root = $treeBuilder->root('dinecat_theme');

        $resource = $root->children()->arrayNode('resource')->addDefaultsIfNotSet()->children();
        $resource->scalarNode('default_theme')->defaultValue('main');
        $resource->scalarNode('default_preset')->defaultValue('default');
        $resource->arrayNode('host')->addDefaultsIfNotSet()->children();

        $storage = $root->children()->arrayNode('storage');
        $options = $storage->isRequired()->cannotBeEmpty()->children();
        $options->enumNode('type')->isRequired()->cannotBeEmpty()->values(['redis', 'sqlite']);
        $options->scalarNode('data_path');
        $options->scalarNode('host')->defaultValue('127.0.0.1');
        $options->integerNode('port')->defaultValue(6379);
        $options->scalarNode('password');
        $options->integerNode('database');
        $options->scalarNode('namespace')->defaultValue('assert');
        $options->floatNode('ttl')->defaultValue(2.5);
        $options->scalarNode('connection');

        $storage->validate()
            ->ifTrue(function ($v) { return $v['type'] === 'redis'; })
            ->then(function ($v) {
                if (empty($v['database'])) {
                    throw new \LogicException('You must specify database number for redis driver.');
                }

                unset($v['data_path']);

                if (array_key_exists('connection', $v) && $v['connection']) {
                    unset($v['host'], $v['port'], $v['password'], $v['ttl']);
                }

                return $v;
            });

        $storage->validate()
            ->ifTrue(function ($v) { return $v['type'] === 'sqlite'; })
            ->then(function ($v) {
                if (empty($v['data_path'])) {
                    throw new \LogicException('You must provide path to database for sqlite driver.');
                }

                unset($v['host'], $v['port'], $v['password'], $v['ttl'], $v['database'], $v['namespace']);

                return $v;
            });

        return $treeBuilder;
    }
}
