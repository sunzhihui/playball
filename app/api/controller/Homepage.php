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

namespace app\api\controller;

/**
 * 首页控制器
 */
class Homepage extends ApiBase
{
    protected $filed='a.*,m.nickname,t.path as listpic,s.path as detailpic';
    /**
     * 首页
     */
    public function home()
    {

        //首页轮播
        $parm=$this->param;
        $list['adv'] = $this->logicHome->getAdvList();

        //可提现余额
        $list['score']=0;
        //gtype=1时
        //任务类型 1新人 2日常 3手游试玩
        //gtype=2时
        //任务类型 1新人 2日常 3星球福利社 4进阶任务 5今日试用
        $where['a.gtype'] =  2;
        $where['a.status'] =  1;
        if(!empty($parm['user_token'])){
            $userInfo = get_member_by_token($parm['user_token']);
            $where['userid'] = $userInfo->user_id;
            $list['score'] = $this->logicHome->getScore(['userid'=>$where['userid']],'score');
            $where['userid'] = $parm['userid'];
        }
        $list['today_top'] = $this->logicHome->getTaskList(array_merge($where,['a.type'=>2,'iftop'=>1]),$this->filed,'',false);
        //高额奖励推荐
        $list['moreMoney'] = $this->logicHome->getTaskList(array_merge($where,['a.gstatus'=>1]),$this->filed,'',false);

        return $this->apiReturn($list);
    }


    //任务中心
    public function taskCenter(){
        $parm=$this->param;
        //每日任务（数量）
        $where['a.gtype'] =  2;
        $where['a.status'] =  1;
        $result['endtaskCount']=0;
        $scoredata=[];
        $ifget=0;
        $score_taskend_config=parse_config_array('score_taskend');
        if(!empty($parm['user_token'])){
            $userInfo = get_member_by_token($parm['user_token']);
            $where['userid'] = $userInfo->user_id;
            $uinfo=$this->logicUser->getuinfo(['userid'=>$userInfo['userid']],'userid,outendcount,getendcount');
            foreach($score_taskend_config as $k=>$v){
                strpos($uinfo['getendcount'],$k)?$ifget=1:'';//等于1可领取
                strpos($uinfo['outendcount'],$k)?$ifget=2:'';//等于2已领取
                $scoredata[]=['count'=>$k,'score'=>$v,'ifget'=>$ifget];
            }
        }else{
            foreach($score_taskend_config as $k=>$v){
                $scoredata[]=['count'=>$k,'score'=>$v,'ifget'=>$ifget];
            }
        }

        $result['score_taskend_config'] =$scoredata;

        if(!empty($parm['userid'])){
            $where['userid'] = $parm['userid'];
            $endids=$this->logicHome->endids($parm['userid']);//已完成的gameid;
            !empty($endids) && $result['endtaskCount']=count($endids)?:0;
        }
        //新手任务
        $result['newtask'] = $this->logicHome->getTaskList(array_merge($where,['a.type'=>1]),$this->filed,'',false);

        //每日任务
        $result['todaytask'] = $this->logicHome->getTaskList(array_merge($where,['a.type'=>2]),$this->filed,'',false);

        $result['alltaskCount']=count($result['todaytask'])?:0;//所有日常任务的数量
        //星球福利社
        $result['balltask'] = $this->logicHome->getTaskList(array_merge($where,['a.type'=>3]),$this->filed,'',3);

        //进阶任务
        $result['movetask'] = $this->logicHome->getTaskList(array_merge($where,['a.type'=>4]),$this->filed,'',false);

        return $this->apiReturn($result);
    }


    //星球福利社
    public function ballboon(){
        $parm=$this->param;
        $where['a.gtype'] =  2;
        $where['a.status'] =  1;
        if(!empty($parm['userid'])){
            $where['userid'] = $parm['userid'];
        }
        $result = $this->logicHome->getTaskList(array_merge($where,['a.type'=>3]),$this->filed,'',true);
        $pic=config('ballboon_pic');
        $result['pic']='';
        !empty($pic) && $result['pic']=$this->logicHome->getimgUrl($pic);
        return $this->apiReturn($result);
    }
    //星球游乐园
    public function ballgame(){
        $parm=$this->param;
        $where['a.gtype'] =  1;//游戏任务
        $where['a.status'] =  1;
        if(!empty($parm['user_token'])){
            $userInfo = get_member_by_token($parm['user_token']);
            $where['userid'] = $userInfo->user_id;
        }
        //今日游戏任务数量+金钱
        $result['today_count']=$this->logicHome->getTaskList(array_merge($where,['a.type'=>2]),'count(1) as gcount,IFNULL(sum(a.score),0) as scores','',false);

        //试玩手游可领取金币
        $result['hand_count']=$this->logicHome->getTaskList(array_merge($where,['a.type'=>3]),'count(1) as gcount,IFNULL(sum(a.score),0) as scores','',false);

        //今日游戏推荐
        $result['new_game'] = $this->logicHome->getTaskList(array_merge($where,['a.type'=>1,'iftop'=>1]),$this->filed.',a.gstatus','',false);

        //即点即玩(H5游戏)
        $result['now_game'] = $this->logicHome->getTaskList(array_merge($where,['a.iftop'=>1,'a.if_h5'=>1]),$this->filed.',a.gstatus','',false);

        //玩手游赚钱(iftop)
        $result['head_game'] = $this->logicHome->getTaskList(array_merge($where,['a.type'=>2]),$this->filed.',a.gstatus','',false);

        return $this->apiReturn($result);
    }
    //任务详情（含任务完成情况，必须传user_token）
    public function Taskdetail(){
        $parm=$this->param;
        return $info=$this->logicHome->getTaskdetail($parm);
    }

}
