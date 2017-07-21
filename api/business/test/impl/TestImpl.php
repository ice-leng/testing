<?php
/**
 * Created by PhpStorm.
 * User: lengbin
 * Date: 2017/7/12
 * Time: 上午11:44
 */

namespace business\test\impl;


use business\common\BaseService;
use business\test\dao\TestAccept;
use business\test\dao\TestCase;
use business\test\dao\TestItem;
use business\test\dao\TestLog;
use business\test\dao\TestSetCase;
use business\test\dao\TestWorkflow;
use business\test\TestInterface;
use common\helpers\CodeHelper;
use yii\base\Exception;

class TestImpl extends BaseService implements TestInterface
{
    private $_workFlow;
    private $_item;
    private $_case;
    private $_accept;
    private $_log;
    private $_setCase;

    public function __construct(array $config = [])
    {
        $this->_workFlow = new TestWorkflow();
        $this->_item = new TestItem();
        $this->_setCase = new TestSetCase();
        $this->_case = new TestCase();
        $this->_accept = new TestAccept();
        $this->_log = new TestLog();
        parent::__construct($config);
    }


    /**
     * 获得测试流程列表
     *
     * @param int   $projectId 项目id
     * @param array $params    ['name' => 'xxx']
     *
     * @return mixed [
     *                  [
     *
     *                  ],
     *              ]
     *
     * @author lengbin(lengbin0@gmail.com)
     */
    public function getTestWorkflowList($projectId, array $params = [])
    {
        // TODO: Implement getTestWorkflowList() method.
    }

    /**
     * 测试流程列表 表单验证规则
     *
     * @return mixed
     * @author lengbin(lengbin0@gmail.com)
     */
    public function getTestWorkflowFormValidate()
    {
        return $this->createFromValidate($this->_workFlow);
    }

    /**
     * 通过测试流程id获得测试流程信息
     *
     * @param int $id
     *
     * @return object
     * @author lengbin(lengbin0@gmail.com)
     */
    public function getTestWorkflowById($id)
    {
        $workflow = $this->_workFlow->getTestWorkflowById($id);
        if (empty($workflow)) {
            $this->invalidParamException(CodeHelper::SYS_PARAMS_ERROR, '测试流程不存在');
        }
        return $workflow;
    }

    /**
     * 通过测试流程id获得测试流程项信息
     *
     * @param int $workflowId
     *
     * @return mixed
     * @author lengbin(lengbin0@gmail.com)
     */
    public function getTestItemByWorkflowId($workflowId)
    {
        return $this->_item->getTestItemByWorkflowId($workflowId);
    }

    /**
     * 通过测试流程id获得测试流程排序
     *
     * @param int $id
     *
     * @return mixed
     * @author lengbin(lengbin0@gmail.com)
     */
    public function getTestWorkflowOrder($id = 0)
    {
        if ($id > 0) {
            $workflow = $this->getTestWorkflowById($id);
            $order = $workflow->order;
        } else {
            $workflow = $this->_workFlow->getMaxOrder();
            $order = $workflow + 1;
        }
        return $order;
    }

    /**
     * 测试项 表单验证规则
     *
     * @return mixed
     * @author lengbin(lengbin0@gmail.com)
     */
    public function getTestItemFormValidate()
    {
        return $this->createFromValidate($this->_item);
    }

    /**
     * 测试设置用例 表单验证规则
     *
     * @return mixed
     * @author lengbin(lengbin0@gmail.com)
     */
    public function getTestSetCaseFormValidate()
    {
        return $this->createFromValidate($this->_setCase);
    }

    /**
     * 测试验收 表单验证规则
     *
     * @return mixed
     * @author lengbin(lengbin0@gmail.com)
     */
    public function getTestAcceptFormValidate()
    {
        return $this->createFromValidate($this->_accept);
    }

    /**
     * 通过项目id 获得所有测试流程
     *
     * @param int $pid project id
     *
     * @return array [ [id => name], ... ]
     * @author lengbin(lengbin0@gmail.com)
     */
    public function getTestWorkflowByProjectId($pid)
    {
        $testWorkflow = $this->_workFlow->getTestWorkflowByProjectId($pid);
        $data = [];
        foreach ($testWorkflow as $flow) {
            $data[$flow['id']] = $flow['name'];
        }
        return $data;
    }

    /**
     * 添加 / 更新 测试流程
     *
     * @param array $params     [
     *                          'flow' => [],
     *                          'item' => [],
     *                          'setCase' => [],
     *                          'accept => []'
     *                          ]
     *
     * @return array
     * @author lengbin(lengbin0@gmail.com)
     * @throws Exception
     */
    public function update(array $params)
    {
        $workflow = [];
        $error = [];
        $flow = isset($params['flow']) ? $params['flow'] : [];
        $pid = isset($flow['project_id']) ? $flow['project_id'] : 0;
        $flows = $this->getTestWorkflowByProjectId($pid);
        $items = isset($params['item']) ? $params['item'] : [];
        $setCases = isset($params['setCase']) ? $params['setCase'] : [];
        $accepts = isset($params['accept']) ? $params['accept'] : [];
        $con = \Yii::$app->db->beginTransaction();
        try {
            try {
                $workflow = $this->_workFlow->updateTestWorkflow($flow, $flows);
            } catch (Exception $e) {
                if ($e->getCode() === CodeHelper::SYS_PARAMS_ERROR) {
                    throw $e;
                }
                $error['flow'] = $e->getMessage();
            }
            $flowId = isset($workflow['id']) ? $workflow['id'] : 0;
            $this->_item->deleteTestItem($flowId);
            foreach ($items as $i => $item) {
                $itemObj = [];
                $item['test_workflow_id'] = $flowId;
                try {
                    $itemObj = $this->_item->updateTestItem($item);
                } catch (Exception $e) {
                    $error['item'][$i] = $e->getMessage();
                }
                $setCase = isset($setCases[$i]) ? $setCases[$i] : [];
                $acceptes = isset($accepts[$i]) ? $accepts[$i] : [];
                $itemId = isset($itemObj['id']) ? $itemObj['id'] : 0;
                $this->_setCase->deleteTestSetCase($itemId);
                $this->_accept->deleteTestAccept($itemId);
                foreach ($setCase as $m => $case) {
                    $case['test_item_id'] = $itemId;
                    $case['is_required'] = $case['is_required'] ? 1 : 0;
                    $case['is_xss'] = $case['is_xss'] ? 1 : 0;
                    $case['is_sql'] = $case['is_sql'] ? 1 : 0;
                    try {
                        $this->_setCase->updateTestSetCase($case);
                    } catch (Exception $e) {
                        $error['setCase'][$i][$m] = $e->getMessage();
                    }
                }
                foreach ($acceptes as $n => $accept) {
                    $accept['test_item_id'] = $itemId;
                    try {
                        $this->_accept->updateTestAccept($accept);
                    } catch (Exception $e) {
                        $error['accept'][$i][$n] = $e->getMessage();
                    }
                }
            }
            $con->commit();
        } catch (Exception $e) {
            $con->rollBack();
        }
        if (!empty($error)) {
            $this->invalidFormException(CodeHelper::SYS_FORM_ERROR, $error);
        }
        return $workflow;
    }

    /**
     * 是否执行测试
     *
     * @param int $workflowId
     *
     * @return mixed
     * @author lengbin(lengbin0@gmail.com)
     */
    public function isRun($workflowId)
    {
        $case = $this->_case->getTestCaseByWorkflowId($workflowId);
        return empty($case) ? false : true;
    }

    /**
     * batch case
     *
     * @param array $setCase
     * @param int   $workflowId
     * @param int   $isRight
     * @param int   $type
     *
     * @return array
     * @author lengbin(lengbin0@gmail.com)
     */
    private function _getBatchCase($setCase, $workflowId, $isRight = 0, $type = 0)
    {
        switch ($type) {
            case 1:
                $name = '|不能为空';
                $params = '';
                break;
            case 2:
                $name = '|xss攻击';
                $params = '<img src=”javacript:alert(/XSS/)”></img>';
                break;
            case 3:
                $name = '|sql注入';
                $params = ' or 1=1 ';
                break;
            default:
                $name = '';
                $params = $setCase['element_params'];
                break;
        }
        return [
            $workflowId,
            $setCase['id'],
            $setCase['name'] . $name,
            $setCase['element_type'],
            $setCase['event_type'],
            $setCase['element'],
            $params,
            $setCase['wait_time'],
            $isRight,
            0,
            time(),
            time(),
        ];
    }

    /**
     * 通过流程id 生成测试用例
     *
     * @param int $workflowId
     *
     * @return mixed
     * @author lengbin(lengbin0@gmail.com)
     * @throws \Exception
     */
    public function generateCase($workflowId)
    {
        $cases = [];
        $this->getTestWorkflowById($workflowId);
        $items = $this->getTestItemByWorkflowId($workflowId);
        foreach ($items as $item) {
            $setCases = $item->setCases;
            foreach ($setCases as $setCase) {
                $isRequired = $setCase['is_required'] ? 1 : 0;
                $isXss = $setCase['is_xss'] ? 1 : 0;
                $isSql = $setCase['is_sql'] ? 1 : 0;
                if ($isRequired) {
                    $cases[] = $this->_getBatchCase($setCase, $workflowId, 0, 1);
                }
                if ($isXss) {
                    $cases[] = $this->_getBatchCase($setCase, $workflowId, 0, 2);
                }
                if ($isSql) {
                    $cases[] = $this->_getBatchCase($setCase, $workflowId, 0, 3);
                }
                $cases[] = $this->_getBatchCase($setCase, $workflowId, 1);
            }
        }
        $con = \Yii::$app->db->beginTransaction();
        try {
            $this->_case->deleteTestCaseByWorkflowId($workflowId);
            $this->_case->batchAddTestCase($cases);
            $con->commit();
        } catch (Exception $e) {
            \Yii::error($e->getMessage());
            $con->rollBack();
            throw $e;
        }
    }
}