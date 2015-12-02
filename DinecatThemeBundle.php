<?php

/**
 * This file is part of the DinecatThemeBundle package.
 * @copyright   2015 UAB Dinecat, http://dinecat.com/
 * @license     http://dinecat.com/licenses/mit MIT License
 * @link        https://github.com/dinecat/ThemeBundle
 */

namespace Dinecat\ThemeBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Dinecat\ThemeBundle\DependencyInjection\Compiler\DriverCompilerPass;

/**
 * Bundle identifier.
 * @package DinecatThemeBundle
 * @author  Mykola Zyk <relo.san.pub@gmail.com>
 */
class DinecatThemeBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new DriverCompilerPass());
    }
}
