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

namespace OrangeHRM\Tests\Performance\Api;

use OrangeHRM\Framework\Services;
use OrangeHRM\Performance\Api\PerformanceReviewAPI;
use OrangeHRM\Tests\Util\EndpointIntegrationTestCase;
use OrangeHRM\Tests\Util\Integration\TestCaseParams;

/**
 * @group Performance
 * @group APIv2
 */
class PerformanceReviewAPITest extends EndpointIntegrationTestCase
{
    /**
     * @dataProvider dataProviderForTestGetAll
     */
    public function testGetAll(TestCaseParams $testCaseParams): void
    {
        $this->populateFixtures('PerformanceReviewAPITest.yaml');
        $this->createKernelWithMockServices([Services::AUTH_USER => $this->getMockAuthUser($testCaseParams)]);
        $this->registerMockDateTimeHelper($testCaseParams);
        $this->registerServices($testCaseParams);

        $api = $this->getApiEndpointMock(PerformanceReviewAPI::class, $testCaseParams);
        $this->assertValidTestCase($api, 'getAll', $testCaseParams);
    }

    public function dataProviderForTestGetAll(): array
    {
        return $this->getTestCases('PerformanceReviewAPITestCases.yaml', 'GetAll');
    }

    /**
     * @dataProvider dataProviderForTestDelete
     */
    public function testDelete(TestCaseParams $testCaseParams): void
    {
        $this->populateFixtures('PerformanceReviewAPITest.yaml');
        $this->createKernelWithMockServices([Services::AUTH_USER => $this->getMockAuthUser($testCaseParams)]);
        $this->registerMockDateTimeHelper($testCaseParams);
        $this->registerServices($testCaseParams);

        $api = $this->getApiEndpointMock(PerformanceReviewAPI::class, $testCaseParams);
        $this->assertValidTestCase($api, 'delete', $testCaseParams);
    }

    public function dataProviderForTestDelete(): array
    {
        return $this->getTestCases('PerformanceReviewAPITestCases.yaml', 'Delete');
    }

    public function testCreate(): void
    {
        $api = new PerformanceReviewAPI($this->getRequest());
        $this->expectNotImplementedException();
        $api->create();
    }

    public function testGetValidationRuleForCreate(): void
    {
        $api = new PerformanceReviewAPI($this->getRequest());
        $this->expectNotImplementedException();
        $api->getValidationRuleForCreate();
    }

    public function testUpdate(): void
    {
        $api = new PerformanceReviewAPI($this->getRequest());
        $this->expectNotImplementedException();
        $api->update();
    }

    public function testGetValidationRuleForUpdate(): void
    {
        $api = new PerformanceReviewAPI($this->getRequest());
        $this->expectNotImplementedException();
        $api->getValidationRuleForUpdate();
    }

    public function testGetOne(): void
    {
        $api = new PerformanceReviewAPI($this->getRequest());
        $this->expectNotImplementedException();
        $api->getOne();
    }

    public function testGetValidationRuleForGetOne(): void
    {
        $api = new PerformanceReviewAPI($this->getRequest());
        $this->expectNotImplementedException();
        $api->getValidationRuleForGetOne();
    }
}
