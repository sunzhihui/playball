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
use think\Db;
use app\common\model\ModelBase;

/**
 * 回收站逻辑
 */
class Score extends AdminBase
{

    /**
     * 获取积分排名
     *
     */
    public function getScoreList($where = ['type'=>'1'], $field = 'sum(a.score) as scores,a.create_time,u.photo,u.userid,u.name', $order = '')
    {
        $where['a.status']=['=',1];
        $where['a.type']=['=',1];

        $list=Db::table('yb_score')
            ->alias('a')
            ->join('user u','a.userid = u.userid')
            ->where($where)
            ->field($field)
            ->group('a.userid')
            ->paginate(DB_LIST_ROWS);
        $this->setCache(new ModelBase(), $list);
        return $list;
    }
    /**
     * 获取积分列表列表搜索条件
     */
    public function getWhere($data = [])
    {

        $where = [];
        !empty($data['search_data']) && $where['u.name'] = ['like', '%'.$data['search_data'].'%'];
        !empty($data['timestart']) ? $where['a.create_time'] = ['<=', strtotime($data['timestart'])] :$where['a.create_time'] = ['<=', strtotime(date("Y-m-d"))];//为空则获取当天0点数据
        !empty($data['timeend']) ? $where['a.create_time'] = ['<=', strtotime($data['timeend'])] :$where['a.create_time'] = ['>=', strtotime(date('Y-m-d',strtotime('+1 day')))];//为空则获取当晚24点之前数据
        return $where;
    }

    public function getoldScoreList($where = '', $field = 'a.score,a.create_time,a.score,a.status,a.money,u.photo,u.userid,u.name', $order = 'a.scoreid desc')
    {
        $where['a.type']=['=',2];

        $this->modelScore->alias('a');

        $join = [
            [SYS_DB_PREFIX . 'user u', 'a.userid = u.userid'],
        ];

        $this->modelScore->join = $join;

        $list=$this->modelScore->getList($where, $field, $order);

        return $list;
    }


}
