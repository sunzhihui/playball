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
namespace app\common\logic;
use think\Db;
use app\api\logic\Home;

/**
 * 回收站逻辑
 */
class Adv extends LogicBase
{

    /**
     * 获取广告列表
     */
    public function getAdvList($where = [], $field = 'a.*,m.nickname', $order = '')
    {

        $this->modelAdv->alias('a');

        $join = [
            [SYS_DB_PREFIX . 'member m', 'a.member_id = m.id'],
        ];

        $where['a.' . DATA_STATUS_NAME] = ['neq', DATA_DELETE];

        $this->modelAdv->join = $join;
        //dy($where);
        $list=$this->modelAdv->getList($where, $field, $order);
        return $list;
    }
    /**
     * 获取广告列表搜索条件
     */
    public function getWhere($data = [])
    {

        $where = [];
        !empty($data['search_data']) && $where['a.name|a.describe'] = ['like', '%'.$data['search_data'].'%'];
        !empty($data['type']) && $where['a.type'] = ['=',$data['type']];
        !empty($data['tasktype']) && $where['a.tasktype'] = ['=',$data['tasktype']];
        return $where;
    }

    /**
     * 广告信息编辑
     */
    public function advEdit($data = [])
    {
        //验证器
//        $validate_result = $this->validateAdv->scene('edit')->check($data);
//
//        if (!$validate_result) {
//
//            return [RESULT_ERROR, $this->validateArticle->getError()];
//        }
        $url = url('advList');

        empty($data['id']) && $data['member_id'] = MEMBER_ID;
        $data['content'] = html_entity_decode($data['content']);//html处理
        $result = $this->modelAdv->setInfo($data);

        $handle_text = empty($data['id']) ? '新增' : '编辑';
        //写日志
        $result && action_log($handle_text, '广告' . $handle_text . '，name：' . $data['name']);

        return $result ? [RESULT_SUCCESS, '广告操作成功', $url] : [RESULT_ERROR, $this->modelAdv->getError()];
    }
    /**
     * 获取广告信息
     */
    public function getAdvInfo($where = [], $field = true)
    {
        $info = $this->modelAdv->getInfo($where, $field);
        if($info['cover_id']){
            $info['img']=Home::getimgUrl($info['cover_id']);
        }else{
            $info['img']='';
        }

        return $info;
    }

}
