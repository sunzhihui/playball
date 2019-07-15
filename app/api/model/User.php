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

namespace app\api\model;
use app\common\model\ModelBase;
use app\common\model\Score;

use think\Db;

class User extends ModelBase
{

    /**
     * 获取邀请好友数量
     */

    public function getInviteCount($where = [],$stat_type = 'count', $field = 'id')
    {

        $pcount = $this->modelYqlist->stat($where,$stat_type = 'count', $field = 'id');
        return $pcount;
    }

    /**
     * 处理收益（支出）明细列表数据
     */
    public function groupVisit($list,$type,$status,$user_id)
    {
        $year = date('Y');
        $listArr = [];
        foreach ($list as $k=>$v) {

            if ($year == date('Y', $v['create_time'])) {
                $date = date('m月d日', $v['create_time']);
            } else {
                $date = date('Y年m月d日', $v['create_time']);
            }
            $listArr[$date]['date'] = $date;
            $time = date('Y-m-d',$v['create_time']);
            $map = [
                'type' => ['=',$type],
                'status' => ['<>',$status],
                'userid' => $user_id,
                'create_time' => ['between time',[$time . '00:00:00',$time . '23:59:59']]
            ];
            $todayTotal = Score::where($map)->sum('score');
//            $todayTotal=Db::table('yb_score')->where($map)->sum('score');
            $listArr[$date]['todayTotal'] = $todayTotal;
            $listArr[$date]['list'][] = $v;
        }
        return $listArr;
    }

    /**
     * 获取邀请好友应得积分
     */
    public function rewardPoints($getValue,$pcount)
    {
        $getData = [];
        foreach($getValue as $k=>$v){
            $getData[] = $k.','.$v;
        }
        $reward= 0;
        foreach($getData as $k=>$val){
            $next = explode(',',$val);
            $last['0'] = '-1';
            if($k>0){
                $last = explode(',',$getData[$k-1]);
            }
            if($pcount <=  $next['0'] && $pcount > $last['0']){
                $reward = (int)$next['1'];
                break;
            }
        }
        return $reward;
    }

    /**
     * 奖励积分并先存数据库,用户积分增加，积分表加数据,
     */
    public function spcmoneyAdd($userInfo,$sendValue,$inviteUser,$score_bl,$scoreTotal)
    {

        Db::startTrans();
        try{
            $sendData = [];
            foreach($sendValue as $k=>$val){
                $sendData[] = $k.','.$val;
            }
            $scoreSum = 0;
            foreach($sendData as $k=>$v){
                $vo = explode(',',$v);
                $score = (int)$vo['1'] * (int)$score_bl;//积分
                //已发放总积分
                $scoreSum +=$score;
                if($k == 0){
                    $this->modelScore->setInfo([
                        'userid' => $inviteUser['userid'],
                        'type' => 1,
                        'status' => 1,
                        'remark' => '邀请好友',
                        'score' => $score,
                        'create_time' =>time()
                    ]);
                    $this->modelSpcmoney->setInfo([
                        'score' => $score,
                        'userid' => $inviteUser['userid'],
                        'status' =>1,
                        'cantixian' => 1,
                        'type' =>2,
                        'create_time' => time()
                    ]);

                    $this->modelUser->setFieldValue(['userid'=>$inviteUser['userid']],$field = 'score', $value = $inviteUser['score']+$score);
                    $this->modelUser->setFieldValue(['userid'=>$userInfo->user_id],$field = 'pid', $value = $inviteUser['userid']);

                }
                $status = $vo['0'] == 1 ? 1 : 0;
                $ifsend = $vo['0'] == 1 ? 1 : 0;
                $day = $vo['0'] - 1;
                $time = date("Y-m-d", strtotime('+'.$day."day"));
                if($vo['1'] == 0){
                    //剩余积分 = 总积分 - 已发放积分
                    $score = $scoreTotal - $scoreSum;
                }
                $this->modelYqmoney->setInfo([
                    'userid' => $inviteUser['userid'],
                    'day' => $vo['0'],
                    'status' =>$status,
                    'ifsend' => $ifsend,
                    'paydate' => $time,
                    'cid' => $userInfo->user_id,
                    'score' => $score,
                ]);
            }
            Db::commit();
        }catch (\Exception $e) {
            Db::rollback();
            return false;
        }
        return true;

    }

    //邀请表加数据
    public function yqlistAdd($userInfo,$inviteUser,$score)
    {
        $result = $this->modelYqlist->setInfo([
            'userid' => $userInfo->user_id,
            'pid' => $inviteUser['userid'],
            'score' => $score,
            'status' => 1,
            'create_time' => time()
        ]);

        if($result) {
            return true;
        }else{
            return false;
        }
    }

}
