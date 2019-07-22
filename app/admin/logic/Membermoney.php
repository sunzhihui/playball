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

    public function getScoreList($where = [], $field = 's.*,u.phone,photo,name,openid,zfbnum,zfbname', $order = 's.create_time desc', $paginate = DB_LIST_ROWS)
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
    public function examine($data = [])
    {
        $url=url('tixianlist');

//        $where = ['scoreid' => $param['scoreid']];

        $data['update_time'] = time();

        $result = $this->modelScore->setInfo($data);
        if($result && $data['status'] == 2){
            action_log('提现申请', '提现申请被拒绝，积分已返回余额' . '，scoreid：' . $data['scoreid'] . '，status：' . $data['status']);
            $scoreInfo = $this->modelScore->getInfo(['scoreid' => $data['scoreid']]);
            Db::startTrans();
            try {
                $userInfo = $this->modelUser->getInfo(['userid'=>$scoreInfo['userid']],'score');
                $this->modelUser->setFieldValue(['userid'=>$scoreInfo['userid']],'score',$userInfo['score']+$scoreInfo['score']);
                if($scoreInfo['type'] == 3){
                    $this->modelSpcmoney->updateInfo(['id'=>$scoreInfo['pid']],['status'=>1,'cantixian'=>1]);

                }
                Db::commit();
//                echo "<script>parent.parent.layer.closeAll();parent.parent.layer.msg('添加成功，页面正在刷新');parent.parent.setTimeout('refresh()',2000);</script>";
            }catch (\Exception $e){
                Db::rollback();
            }
        }else{
            action_log('提现申请', '提现申请审核成功' . '，scoreid：' . $data['scoreid'] . '，status：' . $data['status']);
        }
        return $result ? [RESULT_SUCCESS,"<script>parent.parent.layer.closeAll();parent.parent.layer.msg('审核成功，页面正在刷新');parent.parent.setTimeout('refresh()',2000);window.parent.location.reload();</script>",$url] : [RESULT_ERROR, $this->modelScore->getError()];


    }

}