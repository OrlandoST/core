<?php
/**
 * Navigation
 *
 * @copyright   Copyright (c) 2013 Azbe
 * @author      Berdimurat Masaliev <muratmbt@gmail.com>
 */

namespace Core\Navigation\Service;

use Zend\Navigation\Service\AbstractNavigationFactory;

/**
 * Admin navigation factory.
 */
class AdminNavigationFactory extends AbstractNavigationFactory
{
    /**
     * @return string
     */
    protected function getName()
    {
        return 'admin';
    }
}
