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

namespace app\api\logic;
use think\Db;
use app\common\Model\Gamedetail as CommonGamedetail;
use app\api\error\CodeBase;
use app\api\error\Common;
/**
 * 接口文档逻辑
 */
class Home extends ApiBase
{

    /**
     * 获取用户一天中时间段领取记录
     */
    public function getTimeScore($data=[])
    {
        //当前用户能获取奖励的阶段
        $userInfo=$this->logicUser->userInfo($data);
        $userid=$userInfo['userid'];
        //查询当天已获得奖励并等待领取---有最新数据的永远是待领取或正在进行
        $list=$this->modelTimeReward->where(['userid'=>$userid])->order('time desc')->whereTime('create_time','today')->find();
        //将数组键提取出来
        $scoreTimes = parse_config_array('score_times');
        $arr_key=array_keys($scoreTimes);
        if(!empty($list)){
            $t=$list['time'];
            $offset=array_search($t,$arr_key);
            if(in_array($arr_key[$offset+1],$arr_key)){
                return array(['time'=>$arr_key[$offset+1],'score'=>$scoreTimes[$arr_key[$offset+1]]]);
            }else{
                return [];
            }
        }else{
            //返回第一阶段
            $scoreTimes = parse_config_array('score_times');
            $time=array(['time'=>$arr_key[0],'score'=>$scoreTimes[$arr_key[0]]]);
            return $time;
        }
    }

    public function receiveAward($data = [])
    {
        $userInfo = get_member_by_token($data['user_token']);
//        dd($userInfo);
        $scoreTimes = parse_config_array('score_times');
        //判断当前时间段是否已领取
        $list=$this->modelTimeReward->where(['userid'=>$userInfo->user_id,'time'=>$data['time']])->order('time desc')->whereTime('create_time','today')->find();
        $list && $this->apiError(CodeBase::$error);
        Db::startTrans();
        try{
            $timeRewardId = $this->modelTimeReward->setInfo([
                'userid' => $userInfo->user_id,
                'time' => $data['time'],
                'score' => $scoreTimes[$data['time']],
                'create_time' =>time()
            ]);
            $this->modelScore->setInfo([
                'userid' => $userInfo->user_id,
                'type' => 1,
                'status' => 1,
                'pid' => $timeRewardId,
                'remark' => '时间段领取积分',
                'score' => $scoreTimes[$data['time']],
                'create_time' => time()
            ]);
            Db::commit();
            return true;
        }catch (\Exception $ex){
            Db::rollback();
            return CodeBase::$error;
        }

    }

    
    /**
     * 获取接口列表
     */
    public function getAdvList($where = [], $field = 'a.*,m.path as img', $order = '', $paginate = false)
    {

        $this->modelAdv->alias('a');

        $join = [
            [SYS_DB_PREFIX . 'picture m', 'm.id = a.cover_id'],
        ];

        $where['a.status'] = ['=', DATA_NORMAL];

        $this->modelAdv->join = $join;
        return $this->modelAdv->getList($where, $field, 'sort', $paginate);
    }
    public function getScore($where = [], $field = 'a.*,m.path', $order = ''){
        $res=$this->modelUser->getInfo($where, $field, 'sort');
        if($res['score']){
            return $res['score'];
        }else{
            return 0;
        }
    }
    public function getTaskList($where=[],$field ='a.*,m.nickname,t.path as listpic,s.path as detailpic',$order='',$paginate){
        $where['a.status'] = DATA_NORMAL;

        return $this->logicGame->getGameList($where,$field,$order,$order,$paginate);
    }
    public function endids($userid){
        $where=" and a.userid=".$userid;//." and m.type=2 and a.gtype=2"
        return $this->getEndtaskids($where);
    }
    //已完成任务id
    public function getEndtaskids($where,$ifnowday=1){
        $start = date('Y-m-d 00:00:00');
        $end = date('Y-m-d H:i:s');
        $where.= " and a.status=".DATA_NORMAL;

        $sql="select GROUP_CONCAT(gamedetailid) as ids from yb_taskend a left join yb_gamedetail m on a.gamedetailid = m.id where 1 $where";//用户完成的所有子任务id
        if($ifnowday==1){
            $ids1=$ids2=Db::name('taskend')->query("$sql and a.create_time >= unix_timestamp( '$start' ) AND a.create_time <= unix_timestamp( '$end' )");//当日已完成子任务
            $ids=$ids1[0];

        }else{
            $ids1=Db::name('taskend')->query("$sql and m.ifonce = 0");//不可重复任务不受时间限制
            $ids2=Db::name('taskend')->query("$sql and m.ifonce = 1 and a.create_time >= unix_timestamp( '$start' ) AND a.create_time <= unix_timestamp( '$end' )");//当日已完成可重复任务
            $ids=array_merge($ids1[0],$ids2[0]);
        }
        if(count($ids['ids'])>0){
            $res=explode(',',$ids['ids']);
        }else{
            $res=[];
        }
        return $res;//返回子任务ID
    }

    //任务详情含任务完成情况
    function getTaskdetail($parm=[]){
        $userInfo = get_member_by_token($parm['user_token']);
        $userid = $userInfo->user_id;
        $this->modelGame->alias('a');

        $join = [
            [SYS_DB_PREFIX . 'picture t', 't.id = a.cover_id','left'],
            [SYS_DB_PREFIX . 'picture s', 's.id = a.dphoto','left'],
        ];

        $where['a.status'] =  DATA_NORMAL;
        $where['a.id'] =  $parm['gameid'];

        $this->modelGame->join = $join;

        $info=$this->modelGame->getInfo($where,'a.*,t.path as logopic,s.path as listpic');
        empty($info) && $this->apiError(CodeBase::$emptyItem);
        $ids=$this->logicHome->getEndtaskids(' and userid = '.$userid.' and m.gameid='.$info['id'],0);
        $info['havefinish']=$ids;
        //子任务列表
        $info['childs']=get_sington_object('Gamedetail', CommonGamedetail::class)->getList(['gameid'=>$info['id']],'*','sort asc',false);
        !empty($info['img_ids']) && $info['img_ids']=$this->getimgUrl($info['img_ids']);
        !empty($info['gimg_ids']) && $info['gimg_ids']=$this->getimgUrl($info['gimg_ids']);

        //查询历史收益
        $info['oldtotal']=$this->getoldscore($info['id']);

        return $info;
    }
    //查询历史收益
    function getoldscore($pid){
        $oldtotal=$this->modelScore->getInfo(['pid'=>$pid,'type'=>1],'ifnull(sum(score),0) scores');
        $res['oldscoreList']=$this->modelScore->getList(['pid'=>$pid,'type'=>1],'remark,create_time,score','',false);
        $res['oldscore']=$oldtotal['scores'];
        return $res;
    }

    //通过ID获取图片路径
    function getimgUrl($cover_ids){
        $imgids=explode(',',$cover_ids);
        $img_url=[];
        foreach($imgids as $v){
            $img=DB::name('picture')->field('imgurl')->where("id = $v")->value('imgurl');
            $img_url[]=$img;
        }
         return $img_url;
    }
    //完成相应子任务
    function setChildTask($data=[]){
        $userinfo=$this->logicUser->userInfo($data);
        $gameDetailInfo=$this->modelGamedetail->getInfo(['id'=>$data['gamedetailid']]);

        empty($gameDetailInfo) && $this->apiError(CodeBase::$emptyItem);
        if($gameDetailInfo['ifonce']==1){
            //不可重复
            //查询当前子任务是否已完成过
            $taskendInfo=$this->modelTaskend->getInfo(['gamedetailid'=>$data['gamedetailid']]);
        }else{
            //可重复，当日是否完成过
            $taskendInfo=Db::name('taskend')->where(['gamedetailid'=>$data['gamedetailid']])->whereTime('create_time','today')->find();
        }
        !empty($taskendInfo) && $this->apiError(Common::$haveTask);
        Db::startTrans();
        try{
            //添加任务完成记录
            $settaskend=$this->modelTaskend->setInfo(['gamedetailid'=>$data['gamedetailid'],'userid'=>$userinfo['userid']]);
            empty($settaskend) && $this->apiError(Common::$error);
            //送金币，写日志，载入今日已完成数量
            user_log('完成任务', '用户完成' . $gameDetailInfo['name'] . '赠送金币，score：' . $gameDetailInfo['score'],$userinfo['userid']);
            $result=$this->modelScore->setInfo(['userid'=>$userinfo['userid'],'status'=>DATA_NORMAL,'score'=>$gameDetailInfo['score'],'type'=>1,'remark'=>'完成任务送金币','pid'=>$settaskend]);
            //修改余额
            $this->modelUser->setInfo(['score' =>($userinfo['score']+$gameDetailInfo['score'])], ['userid' => $userinfo['userid']]);
            !$result && $this->apiError(Common::$error);


            //当日已完成任务数
            $taskCount=Db::name('taskend')->where(['userid'=>$userinfo['userid']])->whereTime('create_time','today')->count();
            //每日完成数记录并标记可提取

            if($taskCount>0){
                $score_taskend_config=parse_config_array('score_taskend');
                //将数组键提取出来
                $arr_key=array_keys($score_taskend_config);
                $offset=array_search($taskCount,$arr_key);
                if($offset!==false){
                    $num=$arr_key[$offset];
                    //有存在可赠送的金币奖励,判断当前用户今日是否已领取奖励或已存在待领取奖励
                    $outendcount=explode(',',$userinfo['outendcount']);//在不在已领取任务数中出现
                    $getendcount=explode(',',$userinfo['getendcount']);//在不在未领取任务数中出现
                    if(!in_array($num,$outendcount) && !in_array($num,$getendcount)){
                        //都不在他们中出现则可以载入待领取数据中
                        $getendcount[]=$num;
                        $countstr=implode(',',$getendcount);
                        //将数组拼接为字符串并修改数据
                        $res=$this->modelUser->setInfo(['getendcount'=>$countstr],['userid'=>$userinfo['userid']]);
                        !$res && $this->apiError(Common::$error);
                    }

                }
            }

            Db::commit();
            return true;
        }catch (\Exception $e){
            Db::rollback();
            return CodeBase::$error;
        }
    }

}
