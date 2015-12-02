<?php

/**
 * This file is part of the DinecatThemeBundle package.
 * @copyright   2015 UAB Dinecat, http://dinecat.com/
 * @license     http://dinecat.com/licenses/mit MIT License
 * @link        https://github.com/dinecat/ThemeBundle
 */

namespace Dinecat\ThemeBundle\Model\Repository;

/**
 * Resource repository interface.
 * @package DinecatThemeBundle\Model\Repository
 * @author  Mykola Zyk <relo.san.pub@gmail.com>
 */
interface ResourceRepositoryInterface
{
    /**
     * Get resource.
     * @param   string  $theme  Theme name.
     * @param   string  $name   Resource name.
     * @return  array|null
     */
    public function getResource($theme, $name);

    /**
     * Set resource.
     * @param   string  $theme      Theme name.
     * @param   string  $name       Resource name.
     * @param   array   $definition Resource definition.
     * @return  bool    TRUE if resource saved successfully, FALSE otherwise.
     */
    public function setResource($theme, $name, array $definition);

    /**
     * Remove resource.
     * @param   string  $theme  Theme name.
     * @param   string  $name   Resource name.
     * @return  static
     */
    public function removeResource($theme, $name);
}
