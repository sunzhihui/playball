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

        if($result && $param['status'] == 2){

            $scoreInfo = Db::table('yb_score')->where($where)->find();

            Db::startTrans();
            try {
                Db::table('yb_user')->where(['userid'=>$scoreInfo['userid']])->setInc('score',$scoreInfo['score']);

                if($scoreInfo['type'] == 3){
                    Db::table('yb_spcmoney')->where(['id'=>$scoreInfo['pid']])->update(['status'=>1,'cantixian'=>1]);
                    action_log('提现申请', '提现申请被拒绝，积分已返回余额' . '，scoreid：' . $param['scoreid'] . '，status：' . $param['status']);
                }else{
                    action_log('提现申请', '审核成功' . '，scoreid：' . $param['scoreid'] . '，status：' . $param['status']);
                }
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
            }
        }
        return $result ? [RESULT_SUCCESS,'操作成功',$url] : [RESULT_ERROR, $this->modelScore->getError()];


    }

}