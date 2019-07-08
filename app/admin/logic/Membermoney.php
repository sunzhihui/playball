<?php

namespace app\admin\logic;

use think\Db;

/**
 *  积分兑换管理逻辑
 */
class Membermoney extends AdminBase
{

    /**
     * 获取兑换记录信息
     */

    public function getScoreList($where = [], $field = 's.*,u.phone,photo,name,openid,zfbnum,zfbname', $order = '', $paginate = DB_LIST_ROWS)
    {
        $this->modelScore -> alias('s');

        $join = [
            [SYS_DB_PREFIX . 'user u', 's.userid = u.userid', 'LEFT'],
        ];

        $this->modelScore->join = $join;

        $list = $this->modelScore->getList($where,$field,$order,$paginate);
        return $list;
    }

    /**
     * 获取用户待审核兑换积分搜索条件
     */
    public function getWhere($data = [])
    {
        $where = [];
        !empty($data['search_data']) && $where['u.name|u.phone'] = ['like', '%'.$data['search_data'].'%'];

        return $where;
    }

    /**
     * 获取用户兑换记录搜索条件
     */
    public function getPassWhere($data = [])
    {
        $where = [];

        !empty($data['name']) && $where['u.name'] = ['like', '%'.$data['name'].'%'];
        !empty($data['phone']) && $where['u.phone'] = ['like', '%'.$data['phone'].'%'];
        !empty($data['status']) && $where['s.status'] = ['=', $data['status']];

        return $where;
    }

    /**
     * 审核兑换申请
     */
    public function examine($param)
    {
        $url=url('tixianlist');

        $where = ['scoreid' => $param['scoreid']];

        $result = $this->modelScore->updateInfo($where,['status' => $param['status'],'update_time' =>time()]);

        $result && action_log('提现申请', '审核提现申请' . '，scoreid：' . $param['scoreid'] . '，status：' . $param['status']);

        return $result ? [RESULT_SUCCESS,'操作成功',$url] : [RESULT_ERROR, $this->modelScore->getError()];

    }

}