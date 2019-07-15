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

    public function getHelpList($where = [], $field = 'h.*', $order = '', $paginate = DB_LIST_ROWS)
    {
        $this->modelHelp->alias('h');
        $list = $this->modelHelp->getList($where, $field, $order, $paginate);
        $helpType = parse_config_array('help_gethelp');

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
    public function getHelpInfo($where = [],$field = '*')
    {
        return $this->modelHelp->getInfo($where, $field);
    }

    /**
     * 帮助问题删除
     */
    public function helpDel($where = [])
    {

        $result = $this->modelHelp->deleteInfo($where);

        $result && action_log('删除', '帮助问题删除删除，where：' . http_build_query($where));

        return $result ? [RESULT_SUCCESS, '帮助问题删除删除成功'] : [RESULT_ERROR, $this->modelHelp->getError()];
    }

}
