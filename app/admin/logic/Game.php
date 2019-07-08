<?php
// +---------------------------------------------------------------------+
// | OneBase    | [ WE CAN DO IT JUST THINK ]                            |
// +---------------------------------------------------------------------+
// | Licensed   | http://www.apache.org/licenses/LICENSE-2.0 )           |
// +---------------------------------------------------------------------+
// | Author     | Bigotry <3162875@qq.com>                               |
// +---------------------------------------------------------------------+
// | Repository | https://gitee.com/Bigotry/OneBase                      |
// +---------------------------------------------------------------------+

namespace app\admin\logic;

/**
 * 回收站逻辑
 */
class Game extends AdminBase
{

    /**
     * 获取游戏列表
     */
    public function getGameList($where = [], $field = 'a.*,m.nickname', $order = 'a.sort')
    {

        $this->modelGame->alias('a');

        $join = [
            [SYS_DB_PREFIX . 'member m', 'a.member_id = m.id'],
        ];

        $where['a.' . DATA_STATUS_NAME] = ['neq', DATA_DELETE];

        $this->modelGame->join = $join;
        //dy($where);
        $list=$this->modelGame->getList($where, $field, $order);
        return $list;
    }
    /**
     * 获取文章列表搜索条件
     */
    public function getWhere($data = [])
    {

        $where = [];
        !empty($data['search_data']) && $where['a.name|a.describe'] = ['like', '%'.$data['search_data'].'%'];
        return $where;
    }

    /**
     * 游戏信息编辑
     */
    public function gameEdit($data = [])
    {
        //验证器
//        $validate_result = $this->validateGame->scene('edit')->check($data);
//
//        if (!$validate_result) {
//
//            return [RESULT_ERROR, $this->validateArticle->getError()];
//        }
        $url = url('gameList');

        empty($data['id']) && $data['member_id'] = MEMBER_ID;

        //$data['content'] = html_entity_decode($data['content']);//html处理
        $result = $this->modelGame->setInfo($data);

        $handle_text = empty($data['id']) ? '新增' : '编辑';
        //写日志
        $result && action_log($handle_text, '游戏' . $handle_text . '，name：' . $data['name']);

        return $result ? [RESULT_SUCCESS, '游戏操作成功', $url] : [RESULT_ERROR, $this->modelGame->getError()];
    }
    /**
     * 获取游戏信息
     */
    public function getGameInfo($where = [], $field = true)
    {

        $info = $this->modelGame->getInfo($where, $field);
        return $info;
    }

}
