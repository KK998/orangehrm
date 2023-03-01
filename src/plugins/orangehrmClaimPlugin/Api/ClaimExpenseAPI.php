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

namespace OrangeHRM\Claim\Api;

use Exception;
use OpenApi\Annotations as OA;
use OrangeHRM\Admin\Traits\Service\UserServiceTrait;
use OrangeHRM\Claim\Api\Model\ClaimExpenseModel;
use OrangeHRM\Claim\Dto\ClaimExpenseSearchFilterParams;
use OrangeHRM\Claim\Traits\Service\ClaimServiceTrait;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CrudEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Exception\InvalidParamException;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Core\Traits\Service\DateTimeHelperTrait;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Entity\ClaimExpense;
use OrangeHRM\ORM\Exception\TransactionException;

class ClaimExpenseAPI extends Endpoint implements CrudEndpoint
{
    use EntityManagerHelperTrait;
    use ClaimServiceTrait;
    use DateTimeHelperTrait;
    use AuthUserTrait;
    use UserRoleManagerTrait;
    use UserServiceTrait;

    public const PARAMETER_EXPENSE_TYPE_ID = 'expenseTypeId';
    public const PARAMETER_AMOUNT = 'amount';
    public const PARAMETER_NOTE = 'note';
    public const PARAMETER_DATE = 'date';
    public const PARAMETER_REQUEST_ID = 'requestId';
    public const NOTE_MAX_LENGTH = 1000;
    public const PARAMETER_TOTAL_AMOUNT = 'totalAmount';

    /**
     * @OA\Get(
     *     path="/api/v2/claim/requests/{requestId}/expenses",
     *     tags={"Claim/Expenses"},
     *     @OA\Parameter(
     *         name="claimRequestId",
     *         in="query",
     *         required=false,
     *     )
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Claim-ExpenseModel")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="totalAmount", type="number"),
     *             )
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $claimExpenseSearchFilterParams = new ClaimExpenseSearchFilterParams();
        $this->setSortingAndPaginationParams($claimExpenseSearchFilterParams);
        $requestId = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, self::PARAMETER_REQUEST_ID);
        $claimRequest = $this->getClaimService()->getClaimDao()->getClaimRequestById($requestId);
        if ($claimRequest == null) {
            throw $this->getInvalidParamException(self::PARAMETER_REQUEST_ID);
        }
        if (!$this->getUserRoleManagerHelper()->isEmployeeAccessible($claimRequest->getEmployee()->getEmpNumber())) {
            throw $this->getForbiddenException();
        }
        $claimExpenseSearchFilterParams->setRequestId($requestId);
        $claimExpenses = $this->getClaimService()->getClaimDao()->getClaimExpenseList($claimExpenseSearchFilterParams);
        $count = $this->getClaimService()->getClaimDao()->getClaimExpenseCount($claimExpenseSearchFilterParams);
        $total = $this->getClaimService()->getClaimDao()->getClaimExpenseTotal($claimExpenseSearchFilterParams);
        return new EndpointCollectionResult(
            ClaimExpenseModel::class,
            $claimExpenses,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => $count, self::PARAMETER_TOTAL_AMOUNT => $total])
        );
    }

    /**
     * @return ParamRuleCollection
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_REQUEST_ID,
                new Rule(Rules::POSITIVE)
            ),
            ...$this->getSortingAndPaginationParamsRules()
        );
    }

    /**
     * @OA\Post(
     *     path="/api/v2/claim/requests/{requestId}/expenses",
     *     tags={"Claim/Expenses"},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="expenseTypeId", type="integer"),
     *             @OA\Property(property="amount", type="float"),
     *             @OA\Property(property="requestId", type="integer"),
     *             @OA\Property(property="note", type="string"),
     *             @OA\Property(property="date", type="datetime"),
     *             required={"name", "expenseTypeId", "amount", "requestId", "date"}
     *         )
     *     ),
     *     @OA\Response(response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Claim-ExpenseModel"
     *             ),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $claimExpense = new ClaimExpense();
        $this->setClaimExpense($claimExpense);
        return new EndpointResourceResult(ClaimExpenseModel::class, $claimExpense);
    }

    /**
     * @param ClaimExpense $claimExpense
     */
    public function setClaimExpense(ClaimExpense $claimExpense): void
    {
        $this->beginTransaction();
        try {
            $claimRequestId = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, self::PARAMETER_REQUEST_ID);
            $claimRequest = $claimExpense->getDecorator()->getClaimRequestById($claimRequestId);
            if ($claimRequest == null) {
                throw $this->getInvalidParamException(self::PARAMETER_REQUEST_ID);
            }
            if (!$this->getUserRoleManagerHelper()->isEmployeeAccessible($claimRequest->getEmployee()->getEmpNumber())) {
                throw $this->getForbiddenException();
            }
            $claimExpense->getDecorator()->setClaimRequestByRequestId($claimRequestId);
            if ($claimExpense->getDecorator()->getExpenseTypeById($this->getRequestParams()
                    ->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_EXPENSE_TYPE_ID)) == null) {
                throw $this->getInvalidParamException(self::PARAMETER_EXPENSE_TYPE_ID);
            }
            $claimExpense->getDecorator()->setExpenseTypeByExpenseTypeId($this->getRequestParams()
                ->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_EXPENSE_TYPE_ID));
            $claimExpense->setDate($this->getRequestParams()->getDateTime(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_DATE));
            $claimExpense->setAmount($this->getRequestParams()->getFloat(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_AMOUNT));
            $claimExpense->setNote($this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_NOTE));
            $this->getClaimService()->getClaimDao()->saveClaimExpense($claimExpense);
            $this->commitTransaction();
        } catch (InvalidParamException $e) {
            $this->rollBackTransaction();
            throw $e;
        } catch (Exception $e) {
            $this->rollBackTransaction();
            throw new TransactionException($e);
        }
    }

    /**
     * @return ParamRuleCollection
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_REQUEST_ID,
                new Rule(Rules::POSITIVE)
            ),
            new ParamRule(
                self::PARAMETER_EXPENSE_TYPE_ID,
                new Rule(Rules::POSITIVE)
            ),
            new ParamRule(
                self::PARAMETER_AMOUNT,
                new Rule(Rules::FLOAT_TYPE)
            ),
            new ParamRule(
                self::PARAMETER_DATE,
                new Rule(Rules::DATE_TIME)
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_NOTE,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::NOTE_MAX_LENGTH])
                ),
                true
            ),
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v2/claim/requests/{requestId}/expenses",
     *     tags={"Claim/Expenses"},
     *     @OA\RequestBody(ref="#/components/requestBodies/DeleteRequestBody"),
     *     @OA\Response(response="200", ref="#/components/responses/DeleteResponse")
     * )
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        $requestId = $this->getRequestParams()
            ->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, self::PARAMETER_REQUEST_ID);
        $claimRequest = $this->getClaimService()->getClaimDao()->getClaimRequestById($requestId);
        if ($claimRequest == null) {
            throw $this->getInvalidParamException(self::PARAMETER_REQUEST_ID);
        }
        $ids = $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, CommonParams::PARAMETER_IDS);
        if (!$this->getUserRoleManagerHelper()->isEmployeeAccessible($claimRequest->getEmployee()->getEmpNumber())) {
            throw $this->getForbiddenException();
        }
        $this->getClaimService()->getClaimDao()->deleteClaimExpense($claimRequest, $ids);
        return new EndpointResourceResult(ArrayModel::class, $ids);
    }

    /**
     * @return ParamRuleCollection
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_REQUEST_ID,
                new Rule(Rules::POSITIVE)
            ),
            new ParamRule(
                CommonParams::PARAMETER_IDS,
                new Rule(Rules::INT_ARRAY)
            ),
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v2/claim/requests/{requestId}/expenses/{id}",
     *     tags={"Claim/Expenses"},
     *     @OA\PathParameter(
     *         name="id",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Claim-ExpenseModel"
     *             ),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response="404", ref="#/components/responses/RecordNotFound")
     * )
     *
     * @inheritDoc
     */
    public function getOne(): EndpointResult //TODO:Check the claim request state
    {
        $requestId = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, self::PARAMETER_REQUEST_ID);
        $claimExpenseId = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $claimExpense = $this->getClaimService()->getClaimDao()->getClaimRequestExpense($requestId, $claimExpenseId);
        $this->throwRecordNotFoundExceptionIfNotExist($claimExpense, ClaimExpense::class);
        if (!$this->getUserRoleManagerHelper()->isEmployeeAccessible($claimExpense->getClaimRequest()->getEmployee()->getEmpNumber())) {
            throw $this->getForbiddenException();
        }
        return new EndpointResourceResult(ClaimExpenseModel::class, $claimExpense);
    }

    /**
     * @return ParamRuleCollection
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_REQUEST_ID,
                new Rule(Rules::POSITIVE)
            ),
            $this->getValidationDecorator()->requiredParamRule(
                new ParamRule(
                    CommonParams::PARAMETER_ID,
                    new Rule(Rules::POSITIVE)
                )
            ),
        );
    }

    /**
     * @OA\Put(
     *     path="/api/v2/claim/requests/{requestId}/expenses/{id}",
     *     tags={"Claim/Expenses"},
     *     @OA\PathParameter(
     *         name="id",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="expense_type_id", type="integer"),
     *             @OA\Property(property="date", type="string"),
     *             @OA\Property(property="amount", type="float"),
     *             @OA\Property(property="note", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Claim-ExpenseModel"
     *             ),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response="404", ref="#/components/responses/RecordNotFound")
     * )
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $requestId = $this->getRequestParams()
            ->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, self::PARAMETER_REQUEST_ID);
        if ($this->getClaimService()->getClaimDao()->getClaimRequestById($requestId) == null) {
            throw $this->getInvalidParamException(self::PARAMETER_REQUEST_ID);
        }
        $claimExpenseId = $this->getRequestParams()
            ->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $claimExpense = $this->getClaimService()->getClaimDao()->getClaimRequestExpense($requestId, $claimExpenseId);
        $this->throwRecordNotFoundExceptionIfNotExist($claimExpense, ClaimExpense::class);
        if (!$this->getUserRoleManagerHelper()
            ->isEmployeeAccessible($claimExpense->getClaimRequest()->getEmployee()->getEmpNumber())) {
            throw $this->getForbiddenException();
        }
        $this->setClaimExpense($claimExpense);
        return new EndpointResourceResult(ClaimExpenseModel::class, $claimExpense);
    }

    /**
     * @return ParamRuleCollection
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_REQUEST_ID,
                new Rule(Rules::POSITIVE)
            ),
            new ParamRule(
                CommonParams::PARAMETER_ID,
                new Rule(Rules::POSITIVE)
            ),
            new ParamRule(
                self::PARAMETER_EXPENSE_TYPE_ID,
                new Rule(Rules::POSITIVE)
            ),
            new ParamRule(
                self::PARAMETER_NOTE,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::LENGTH, [null, self::NOTE_MAX_LENGTH]),
            ),
            new ParamRule(
                self::PARAMETER_AMOUNT,
                new Rule(Rules::FLOAT_TYPE)
            ),
            new ParamRule(
                self::PARAMETER_DATE,
                new Rule(Rules::DATE_TIME)
            ),
        );
    }
}