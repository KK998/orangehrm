<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */

namespace OrangeHRM\Core\Helper;

use OrangeHRM\Core\Dto\ModuleScreen;
use OrangeHRM\Framework\Http\Request;
use OrangeHRM\Framework\Http\RequestStack;
use OrangeHRM\Framework\ServiceContainer;
use OrangeHRM\Framework\Services;

class ModuleScreenHelper
{
    /**
     * @return ModuleScreen
     */
    public static function getCurrentModuleAndScreen(): ModuleScreen
    {
        $moduleScreen = new ModuleScreen();
        $request = self::getCurrentRequest();
        if ($request) {
            $pathChunks = explode('/', $request->getPathInfo());
            if (isset($pathChunks[1])) {
                $moduleScreen->setModule($pathChunks[1]);
            }
            if (isset($pathChunks[2])) {
                $moduleScreen->setScreen($pathChunks[2]);
            }
        }

        return $moduleScreen;
    }

    /**
     * @return Request|null
     */
    public static function getCurrentRequest(): ?Request
    {
        /** @var RequestStack $requestStack */
        $requestStack = ServiceContainer::getContainer()->get(Services::REQUEST_STACK);
        return $requestStack->getCurrentRequest();
    }
}