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

namespace OrangeHRM\Attendance\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\ModelTrait;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Entity\AttendanceRecord;

/**
 * @OA\Schema(
 *     schema="Attendance-DetailedAttendanceRecordModel",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="attendance_type", type="string"),
 *     @OA\Property(
 *         property="punchIn",
 *         type="object",
 *         @OA\Property(property="utcDate", type="string", format="date"),
 *         @OA\Property(property="utcTime", type="string"),
 *         @OA\Property(property="userDate", type="string", format="date"),
 *         @OA\Property(property="userTime", type="string"),
 *         @OA\Property(property="timezoneOffset", type="string"),
 *         @OA\Property(
 *             property="timezone",
 *             type="object",
 *             @OA\Property(property="name", type="string"),
 *             @OA\Property(property="label", type="string"),
 *         ),
 *         @OA\Property(property="note", type="string")
 *     ),
 *     @OA\Property(
 *         property="punchOut",
 *         type="object",
 *         @OA\Property(property="utcDate", type="string", format="date"),
 *         @OA\Property(property="utcTime", type="string"),
 *         @OA\Property(property="userDate", type="string", format="date"),
 *         @OA\Property(property="userTime", type="string"),
 *         @OA\Property(property="timezoneOffset", type="string"),
 *         @OA\Property(
 *             property="timezone",
 *             type="object",
 *             @OA\Property(property="name", type="string"),
 *             @OA\Property(property="label", type="string"),
 *         ),
 *         @OA\Property(property="note", type="string")
 *     ),
 *     @OA\Property(
 *         property="state",
 *         type="object",
 *         @OA\Property(property="id", type="string"),
 *         @OA\Property(property="name", type="string"),
 *     ),
 *     @OA\Property(
 *         property="employee",
 *         type="object",
 *         @OA\Property(property="empNumber", type="integer"),
 *         @OA\Property(property="employeeId", type="string"),
 *         @OA\Property(property="firstName", type="string"),
 *         @OA\Property(property="lastName", type="string"),
 *         @OA\Property(property="middleName", type="string"),
 *         @OA\Property(property="terminationId", type="integer"),
 *     ),
 * )
 */
class DetailedAttendanceRecordModel implements Normalizable
{
    use ModelTrait;

    public function __construct(AttendanceRecord $attendanceRecord)
    {
        $this->setEntity($attendanceRecord);
        $this->setFilters(
            [
                'id',
                'attendanceType',
                ['getDecorator', 'getPunchInUTCDate'],
                ['getDecorator', 'getPunchInUTCTime'],
                ['getDecorator', 'getPunchInUserDate'],
                ['getDecorator', 'getPunchInUserTime'],
                'punchInTimeOffset',
                'punchInTimezoneName',
                ['getDecorator', 'getPunchInTimezoneLabel'],
                'punchInNote',
                ['getDecorator', 'getPunchOutUTCDate'],
                ['getDecorator', 'getPunchOutUTCTime'],
                ['getDecorator', 'getPunchOutUserDate'],
                ['getDecorator', 'getPunchOutUserTime'],
                'punchOutTimeOffset',
                'punchOutTimezoneName',
                ['getDecorator', 'getPunchOutTimezoneLabel'],
                'punchOutNote',
                'state',
                ['getDecorator', 'getAttendanceState'],
                ['getEmployee', 'getEmpNumber'],
                ['getEmployee', 'getLastName'],
                ['getEmployee', 'getFirstName'],
                ['getEmployee', 'getMiddleName'],
                ['getEmployee', 'getEmployeeId'],
                ['getEmployee', 'getEmployeeTerminationRecord', 'getId']
            ]
        );
        $this->setAttributeNames(
            [
                'id',
                'attendanceType',
                ['punchIn', 'utcDate'],
                ['punchIn', 'utcTime'],
                ['punchIn', 'userDate'],
                ['punchIn', 'userTime'],
                ['punchIn', 'timezoneOffset'],
                ['punchIn', 'timezone','name'],
                ['punchIn', 'timezone','label'],
                ['punchIn', 'note'],
                ['punchOut', 'utcDate'],
                ['punchOut', 'utcTime'],
                ['punchOut', 'userDate'],
                ['punchOut', 'userTime'],
                ['punchOut', 'timezoneOffset'],
                ['punchOut', 'timezone', 'name'],
                ['punchOut', 'timezone', 'label'],
                ['punchOut', 'note'],
                ['state', 'id'],
                ['state', 'name'],
                ['employee', 'empNumber'],
                ['employee', 'lastName'],
                ['employee', 'firstName'],
                ['employee', 'middleName'],
                ['employee', 'employeeId'],
                ['employee', 'terminationId'],
            ]
        );
    }
}
