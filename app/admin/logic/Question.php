<?php

namespace app\admin\logic;

use think\Db;

/**
 *  问卷调查
 */
class Question extends AdminBase
{

    /**
     * 获取问卷调查列表
     */

    public function getQueList($where = [], $field = 'q.*,m.nickname', $order = '', $paginate = DB_LIST_ROWS)
    {
        $this->modelQuestionclass -> alias('q');

        $join = [
            [SYS_DB_PREFIX . 'member m', 'q.member_id = m.id', 'LEFT'],
        ];

        $where['q.' . DATA_STATUS_NAME] = ['neq', DATA_DELETE];

        $this->modelQuestionclass->join = $join;

        $list = $this->modelQuestionclass->getList($where,$field,$order,$paginate);

        return $list;
    }

    /**
     * 获取问卷调查列表搜索条件
     */
    public function getWhere($data = [])
    {

        $where = [];

        !empty($data['search_data']) && $where['q.name|q.remark'] = ['like', '%'.$data['search_data'].'%'];

        return $where;
    }

    /**
     * 问卷调查编辑
     */
    public function questionEdit($data = [])
    {
        $validate_result = $this->validateQuestion->scene('edit')->check($data);

        if (!$validate_result) {

            return [RESULT_ERROR, $this->validateQuestion->getError()];
        }

        if($data['if_new'] == 1){
            $questionInfo = $this->modelQuestionclass->getInfo(['if_new'=>1], "if_new,id");
            $questionInfo->setFieldValue(['id'=>$questionInfo['id']],'if_new',0);
        }
        $url = url('questionList');

        $data['remark'] = html_entity_decode($data['remark']);
        $data['member_id'] = SYS_ADMINISTRATOR_ID;

        $result = $this->modelQuestionclass->setInfo($data);

        $handle_text = empty($data['id']) ? '新增' : '编辑';

        $result && action_log($handle_text, '问卷调查' . $handle_text . '，name：' . $data['name']);

        return $result ? [RESULT_SUCCESS, '问卷调查操作成功', $url] : [RESULT_ERROR, $this->modelQusetionclass->getError()];
    }

    /**
     * 获取问卷调查信息
     */
    public function getQueInfo($where = [], $field = '*')
    {
        return $this->modelQuestionclass->getInfo($where, $field);
    }

    /**
     * 问卷调查删除
     */
    public function questionDel($where = [])
    {

        $result = $this->modelQuestionclass->deleteInfo($where);

        $result && action_log('删除', '问卷调查删除成功，where：' . http_build_query($where));

        return $result ? [RESULT_SUCCESS, '问卷调查删除成功'] : [RESULT_ERROR, $this->modelQusetionclass->getError()];
    }

    /**
     * 问卷调查更改是否最新状态
     */
    public function setType($model = null,$index = null ,$data = [], $status = true)
    {
        if (empty($model) || empty($index)) {

            return [RESULT_ERROR, '非法操作'];
        }

        $model_str = LAYER_MODEL_NAME . $model;

        $obj = $this->$model_str;

        $where = ['id'=>$data['id']];

        $objInfo = $obj->getInfo($where, "$index,status");

        $indexInfo = $obj->getInfo([$index=>1], "$index,id");

        if(!$status && $data['id'] != $indexInfo['id'] && $objInfo[$index] == 0){
            $obj->setFieldValue([$index=>1],$index,0);
        }
        $type = $objInfo[$index] ? 0 : 1;
        if(!$status && $objInfo['status'] != 1 && $type == 1){
            return [RESULT_ERROR, '禁用状态下不能修改为最新状态'];
        }
        $result = $obj->setFieldValue($where,$index,$type);

        $result && action_log('数据状态', '问卷调查是否最新调整成功，id：' . $data['id'] . '，'. $index . '：' . $type);

        return $result ? [RESULT_SUCCESS, '操作成功'] : [RESULT_ERROR, $obj->getError()];
    }

    /**
     * 提示信息
     */
    public function questionMsg()
    {
        return [RESULT_ERROR, '请先取消是最新的状态'];
    }

    /**
     * 获取问题列表
     */

    public function getQueItem($where = [], $field = 'q.*,c.name as q_name', $order = '', $paginate = DB_LIST_ROWS)
    {
        $this->modelQuestion -> alias('q');

        $join = [
            [SYS_DB_PREFIX . 'questionclass c', 'q.questionclassid = c.id', 'LEFT'],
        ];

        $where['q.' . DATA_STATUS_NAME] = ['neq', DATA_DELETE];

        $this->modelQuestion->join = $join;

        $list = $this->modelQuestion->getList($where,$field,$order,$paginate);

        return $list;
    }

    /**
     * 获取问题搜索条件
     */
    public function getQueWhere($data = [])
    {

        $where = [];
        !empty($data['search_data']) && $where['q.name|c.q_name'] = ['like', '%'.$data['search_data'].'%'];

        return $where;
    }

    /**
     * 问卷调查问题编辑
     */
    public function questionItemEdit($data = [])
    {
        $validate_result = $this->validateQuestionItem->scene('edit')->check($data);

        if (!$validate_result) {

            return [RESULT_ERROR, $this->validateQuestionItem->getError()];
        }

        $url = url('questionItem',['id'=>$data['questionclassid']]);
        $data['created_time'] = time();

        $result = $this->modelQuestion->setInfo($data);

        $handle_text = empty($data['id']) ? '新增' : '编辑';

        $result && action_log($handle_text, '问卷调查中的问题' . $handle_text . '，name：' . $data['name']);

        return $result ? [RESULT_SUCCESS, '问卷调查中的问题操作成功', $url] : [RESULT_ERROR, $this->modelQusetion->getError()];
    }

    /**
     * 获取问卷调查问题信息
     */
    public function getQueItemInfo($where = [], $field = '*')
    {
        return $this->modelQuestion->getInfo($where, $field);
    }

    /**
     * 问卷调查问题删除
     */
    public function questionItemDel($where = [])
    {

        $result = $this->modelQuestion->deleteInfo($where);

        $result && action_log('删除', '问卷调查问题删除成功，where：' . http_build_query($where));

        return $result ? [RESULT_SUCCESS, '问卷调查问题删除成功'] : [RESULT_ERROR, $this->modelQusetion->getError()];
    }

    /**
     * 问卷调查问题选项列表
     */
    public function getChooseItem($where = [], $field = '*', $order = '', $paginate = DB_LIST_ROWS)
    {

//        $where[DATA_STATUS_NAME] = ['neq', DATA_DELETE];

        $list = $this->modelQuestionSel->getList($where,$field,$order,$paginate);

        return $list;
    }

    /**
     * 问卷调查问题选项编辑
     */
    public function chooseEdit($data = [])
    {

        $validate_result = $this->validateQuestionSel->scene('edit')->check($data);

        if (!$validate_result) {

            return [RESULT_ERROR, $this->validateQuestionSel->getError()];
        }

        $url = url('chooseitem',['id'=>$data['questionid']]);
        $data['created_time'] = time();

        $result = $this->modelQuestionSel->setInfo($data);

        $handle_text = empty($data['id']) ? '新增' : '编辑';

        $result && action_log($handle_text, '问卷调查中的问题选项' . $handle_text . '，name：' . $data['title']);

        return $result ? [RESULT_SUCCESS, '问卷调查中的问题选项操作成功', $url] : [RESULT_ERROR, $this->modelQuestionSel->getError()];
    }

    /**
     * 获取问卷调查问题信息
     */
    public function getChooseInfo($where = [], $field = '*')
    {
        return $this->modelQuestionSel->getInfo($where, $field);
    }

    /**
     * 问卷调查问题选项删除
     */
    public function chooseDel($where = [])
    {

        $result = $this->modelQuestionSel->deleteInfo($where);

        $result && action_log('删除', '问卷调查问题选项删除成功，where：' . http_build_query($where));

        return $result ? [RESULT_SUCCESS, '问卷调查问题选项删除成功'] : [RESULT_ERROR, $this->modelQuestionSel->getError()];
    }
}