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

        if(!empty($parm['userid'])){
            $list['score'] = $this->logicHome->getScore(['userid'=>$parm['userid']],'score');
            //已完成任务ID
            $endids=$this->logicHome->getEndtaskids(" and a.userid=".$parm['userid']." and m.type=2 and a.gtype=2");
            $where['a.id'] = ['NOT IN',$endids];
        }
        //$filed='a.*,m.nickname,t.path as listpic,s.path as detailpic';
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
        $result['ifgetdayscore']=0;
        $scoredata=[];
        $ifget=0;
        $score_taskend_config=parse_config_array('score_taskend');
        if(!empty($parm['userid'])){
            $uinfo=$this->logicUser->getuinfo(['userid'=>$parm['userid']],'userid,outendcount,getendcount');
            foreach($score_taskend_config as $k=>$v){
                strpos($uinfo['getendcount'],$k)?$ifget=1:'';
                $scoredata[]=['count'=>$k,'score'=>$v,'ifget'=>$ifget];
            }
        }else{
            foreach($score_taskend_config as $k=>$v){
                $scoredata[]=['count'=>$k,'score'=>$v,'ifget'=>$ifget];
            }
        }
        $result['score_taskend_config'] =$scoredata;
        if(!empty($parm['userid'])){
            $where['g.userid'] = $parm['userid'];
            $endids=$this->endids($parm['userid']);//已完成的gameid;
            !empty($endids) && $result['endtaskCount']=count($endids)?:0;
            //$where['a.id'] = ['NOT IN',$endids];
            //$result['endtaskCount']=count($endids);
        }
        //新手任务
        $result['newtask'] = $this->logicHome->getTaskList(array_merge($where,['a.type'=>1]),$this->filed,'',false);

        //每日任务
        $result['todaytask'] = $this->logicHome->getTaskList(array_merge($where,['a.type'=>2]),$this->filed,'',false);

        $result['allendtaskCount']=count($result['todaytask'])?:0;//所有日常任务的数量
        //星球福利社
        $result['balltask'] = $this->logicHome->getTaskList(array_merge($where,['a.type'=>3]),$this->filed,'',3);

        //进阶任务
        $result['movetask'] = $this->logicHome->getTaskList(array_merge($where,['a.type'=>4]),$this->filed,'',false);




        return $this->apiReturn($result);
    }
    public function endids($userid){
        $where=" and a.userid=".$userid." and m.type=2 and a.gtype=2";
        return $this->logicHome->getEndtaskids($where);
    }

    //星球福利社
    public function ballboon(){
        $parm=$this->param;
        $where['a.gtype'] =  2;
        $where['a.status'] =  1;
        if(!empty($parm['userid'])){
            $where['g.userid'] = $parm['userid'];
        }
        $result = $this->logicHome->getTaskList(array_merge($where,['a.type'=>3]),$this->filed,'',true);
        $result['pic']=config('ballboon_pic');
        return $this->apiReturn($result);
    }
    //星球游乐园
    public function ballgame(){
        $parm=$this->param;

        //今日任务数量+金钱
        $where['a.gtype'] =  1;//游戏任务
        $where['a.status'] =  1;
        $result['today_count']=$this->logicHome->getTaskList(array_merge($where,['a.type'=>2]),'count(1) as gcount,IFNULL(sum(a.score),0) as scores','',false);
        //试玩手游可领取金币
        $result['hand_count']=$this->logicHome->getTaskList(array_merge($where,['a.type'=>3]),'count(1) as gcount,IFNULL(sum(a.score),0) as scores','',false);


        //今日任务推荐
        $result['new_game'] = $this->logicHome->getTaskList(array_merge($where,['a.type'=>1]),$this->filed.',a.gstatus','',false);

        $result['day_game'] = $this->logicHome->getTaskList(array_merge($where,['a.type'=>2]),$this->filed.',a.gstatus','',false);

        //即点即玩()
        $result['now_game'] = $this->logicHome->getTaskList(array_merge($where,['a.type'=>2,'a.iftop'=>1]),$this->filed.',a.gstatus','',false);

        //玩手游赚钱(iftop)
        $result['head_game'] = $this->logicHome->getTaskList(array_merge($where,['a.type'=>2]),$this->filed.',a.gstatus','',false);

        return $this->apiReturn($result);
    }
    //app安装完成接口（任务完成表添加记录）
}
