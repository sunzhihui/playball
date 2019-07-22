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
    protected $filed='a.*,m.nickname,t.imgurl as listpic,s.imgurl as detailpic';
    /**
     * 首页
     */
    public function home()
    {
        //首页轮播
        $parm=$this->param;
        $list['adv'] = $this->logicHome->getAdvList(['ifindex'=>1]);
        //可提现余额
        $list['score']=0;
        //gtype=1时
        //任务类型 1新人 2日常 3手游试玩
        //gtype=2时
        //任务类型 1新人 2日常 3星球福利社 4进阶任务 5今日试用
        $where['a.gtype'] =  2;
        $where['a.status'] =  1;
        if(!empty($parm['user_token'])){
            $userInfo = $this->logicUser->userInfo($parm);
            //检验新用户是否注册专属活动
            if ($userInfo['if_havespc'] != 1) {
                $this->logicUser->newpeople_init($parm);
            }
            $where['userid'] = $userInfo['userid'];
            $list['score'] = $this->logicHome->getScore(['userid'=>$where['userid']],'score');
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
            $uinfo=$this->logicUser->userInfo($parm);
            $where['userid'] = $uinfo['userid'];
            $outendcount=explode(',',$uinfo['outendcount']);//在不在已领取任务数中出现
            $getendcount=explode(',',$uinfo['getendcount']);//在不在未领取任务数中出现
            foreach($score_taskend_config as $k=>$v){
                if($k==8){
                    if(in_array($k,$getendcount)){
                        $ifget=1;
                    }
                    if(in_array($k,$outendcount)){
                        $ifget=2;
                    }
                }
                in_array($k,$getendcount)!==false?$ifget=1:'';//等于1可领取
                in_array($k,$outendcount)!==false?$ifget=2:'';//等于2已领取
                $scoredata[]=['count'=>$k,'score'=>$v,'ifget'=>$ifget];
                $ifget=0;
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
        $path=$this->logicHome->getimgUrl($pic);
        !empty($pic) && $result['pic']=$path[0];
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
        return $this->apiReturn($this->logicHome->getTaskdetail($this->param));
    }
    //完成相应子任务
    function setChildTask(){
        return $this->apiReturn($this->logicHome->setChildTask($this->param));
    }

    //时间段奖励
    public function timeReward()
    {
//        $userInfo = get_member_by_token($this->param['user_token']);
//        $scoreTimes = parse_config_array('score_times');
//        //当前用户能获取奖励的阶段
//
//        foreach ($scoreTimes as $k=>$v){
//            $list = $this->logicHome->getTimeScore(['userid'=>$userInfo->user_id,'time'=>$k]);
//            $data = "";
//            if(!$list){
//                $data = explode(',',$k . ',' . $v);
//                break;
//            }
//        }
//        return $this->apiReturn($data);
        return $this->apiReturn($this->logicHome->getTimeScore($this->param));
    }

    //领取奖励
    public function receiveAward()
    {
        return $this->apiReturn($this->logicHome->receiveAward($this->param));
    }

}
