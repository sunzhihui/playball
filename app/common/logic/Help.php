<?php

namespace app\common\logic;

/**
 *  帮助问题逻辑
 */
class Help extends LogicBase
{

    /**
     * 获取帮助问题信息
     */

    public function getHelpList($where = [], $field = '*', $order = '', $paginate = DB_LIST_ROWS)
    {
        $list = $this->modelHelp->getList($where, $field, $order, $paginate);
        return $list;
    }

    /**
     * 获取帮助问题搜索条件
     */
    public function getWhere($data = [])
    {
        $where = [];
        !empty($data['search_data']) && $where['name|content'] = ['like', '%' . $data['search_data'] . '%'];
        return $where;
    }

    /**
     * 帮助问题信息编辑
     */
    public function helpEdit($data = [])
    {

        $validate_result = $this->validateHelp->scene('edit')->check($data);

        if (!$validate_result) {

            return [RESULT_ERROR, $this->validateHelp->getError()];
        }

        $url = url('helpList');

        $data['content'] = html_entity_decode($data['content']);

        $result = $this->modelHelp->setInfo($data);

        $handle_text = empty($data['id']) ? '新增' : '编辑';

        $result && action_log($handle_text, '文章' . $handle_text . '，name：' . $data['name']);

        return $result ? [RESULT_SUCCESS, '文章操作成功', $url] : [RESULT_ERROR, $this->modelHelp->getError()];
    }

    /**
     * 获取帮助问题信息
     */
    public function getHelpInfo($where = [], $field = '*')
    {
        return $this->modelHelp->getInfo($where, $field);
    }

    /**
     * 帮助问题删除
     */
    public function helpDel($where = [])
    {

        $result = $this->modelHelp->deleteInfo($where);

        $result && action_log('删除', '帮助问题删除成功，where：' . http_build_query($where));

        return $result ? [RESULT_SUCCESS, '帮助问题删除删除成功'] : [RESULT_ERROR, $this->modelHelp->getError()];
    }

    /**
     * 设置帮助问题信息是否热门
     */
    public function setIfhot($data = [])
    {
        $data['if_hot'] = $data['if_hot'] == 0 ? 1 : 0;
        $result = $this->modelHelp->setInfo($data);
        $result && action_log('数据状态', '帮助问题信息是否热门调整成功，id：' . $data['id'] . '，if_hot:'. $data['if_hot']);

        return $result ? [RESULT_SUCCESS, '操作成功'] : [RESULT_ERROR, $this->modelHelp->getError()];
    }



    /**
     * 反馈问题信息
     */

    public function feedbackList($where = [], $field = 'f.*,u.name as u_name', $order = '', $paginate = DB_LIST_ROWS)
    {
        $this->modelFeedback->alias('f');

        $join = [
            [SYS_DB_PREFIX . 'user u', 'f.userid = u.userid', 'LEFT'],
        ];

        $this->modelFeedback->join = $join;
        $list = $this->modelFeedback->getList($where, $field, $order, $paginate);

        return $list;
    }

    /**
     * 反馈问题搜索条件
     */
    public function getBackWhere($data = [])
    {
        $where = [];
        !empty($data['search_data']) && $where['f.name|f.content'] = ['like', '%' . $data['search_data'] . '%'];
        return $where;
    }


}
