<?php

/**
 * This file is part of the DinecatThemeBundle package.
 * @copyright   2015 UAB Dinecat, http://dinecat.com/
 * @license     http://dinecat.com/licenses/mit MIT License
 * @link        https://github.com/dinecat/ThemeBundle
 */

namespace Dinecat\ThemeBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds repository driver according to provided configuration.
 * @package DinecatThemeBundle\Config
 * @author  Mykola Zyk <relo.san.pub@gmail.com>
 */
class DriverCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $storage = $container->getParameter('dinecat.theme.resource.storage.options');
        $drvName = 'dinecat.theme.resource.driver';

        if ($storage['type'] === 'sqlite') {

            $drvDef = new Definition(
                'Dinecat\ThemeBundle\Model\Repository\ResourceSqliteRepository',
                [0 => ['data_path' => $storage['data_path']]]
            );
            $container->setDefinition($drvName, $drvDef);

        } elseif ($storage['type'] === 'redis') {

            $connection = null;
            if (array_key_exists('connection', $storage) && $container->hasDefinition($storage['connection'])) {
                $connection = new Reference($storage['connection']);
            }
            unset($storage['connection'], $storage['type']);

            $drvDef = new Definition(
                'Dinecat\ThemeBundle\Model\Repository\ResourceRedisRepository',
                [0 => $connection, 1 => $storage]
            );
            $container->setDefinition($drvName, $drvDef);

        }

        $drv = new Reference($drvName);
        $container->getDefinition('dinecat.theme.resource.distributor')->replaceArgument(0, $drv);
        $container->getDefinition('dinecat.theme.resource.manager')->replaceArgument(0, $drv);
    }
}
