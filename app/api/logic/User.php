<?php

namespace app\api\logic;

use app\api\error\Common as CommonError;
use app\api\error\CodeBase;
use app\api\logic\Common as CommonApi;
use app\common\model\Score;
use think\Db;

class User extends ApiBase
{


    /**
     * 绑定手机号
     */
    public function setPhone($data = [])
    {
        $userInfo = get_member_by_token($data['user_token']);

        if($userInfo->phone) return CommonError::$phoneBindError;
        $validate_result = $this->validateUser->scene('setPhone')->check($data);

        if (!$validate_result) return CommonError::$phoneCodeEmpty;
        if (!CommonApi::is_mobile_phone($data['phone'])) return CommonError::$phoneError;

        begin:
        //根据code_id查询验证码

        $codeInfo = $this->modelCode->getInfo(['id' => $data['code_id']]);

        if ($data['code'] !== /*$codeInfo['code']*/'123456') {
            return CommonError::$codewordError;
        }

        $user = $this->logicUser->getUserInfo(['phone' => $data['phone']]);

        if ($user) return CommonError::$phoneExist;

        $list = [
            'userid' => $userInfo->user_id,
            'phone' => $data['phone'],
            'name' => substr_replace($data['phone'], '****', 3, 4),
        ];
        $result = $this->logicUser->setInfo($list);

        return $result ? $data['phone'] : CommonError::$setPhoneFail;
    }

    /**
     * 我的钱包
     */
    public function wallet($data = [])
    {
        $type = $data['type'] ? $data['type'] : 1;
        $status = $data['type'] ? 2 : 0;

        $userInfo = get_member_by_token($data['user_token']);

        $time = strtotime(date("Y-m-d", strtotime("-3 days")));
        $map = [
            'create_time'=> ['>=', $time],
            'userid' => ['=', $userInfo->user_id],
            'type' => ['=', $type],
            'status' => ['<>', $status]
        ];
        $list = Db::table('yb_score')->where($map)->field(['score','remark','create_time'])->order('create_time desc')->select();
//        $list = $this->modelScore->getList($map,"score,remark,create_time",'create_time desc');

        $arr['list'] = $this->modelUser->groupVisit($list,$type,$status,$userInfo->user_id);

        //今日收益金币
        $post = [
            'type' => 1,
            'userid' => $userInfo->user_id,
            'status' => ['<>',0],
        ];

        $arr['todayScore'] = Score::where($post)->whereTime('create_time','today')->sum('score');
        //累计收益金币
        $arr['totalScore'] = Score::where(['type' => 1,'userid' => $userInfo->user_id,'status'=>1])->sum('score');
        //累计支出金币
        $where = [
            'type' => ['=', 2],
            'status' => ['<>' ,2],
            'userid' => $userInfo->user_id,
        ];
        $arr['outScore'] = Score::where($where)->sum('score');

        $useableScore = $this->modelScore->getInfo(['userid' => $userInfo->user_id],'score');

        $arr['useableScore'] = $useableScore['score'];

        return $arr;
    }


    /**
     * 邀请好友
     */
    public function invite($data = [])
    {
        $userInfo = get_member_by_token($data['user_token']);
        $userList = $this->logicUser->getUserInfo(['userid' => $userInfo->user_id],'pid');
        if($userList['pid']){
            return CommonError::$pidError;
        }
        //邀请人信息
        $inviteUser = $this->logicUser->getUserInfo(['yqcode' => $data['code']],'score,userid');
        //邀请人已经邀请的人数
        $pcount =$this->modelUser->getInviteCount(['pid'=>$inviteUser['userid'],'status'=>1]);
        //查询配置
        $getValue = parse_config_array('yqconfig_get');
        //积分比例
        $score_bl = parse_config_array('score_bl');
        //奖励发放规则
        $sendValue = parse_config_array('yqconfig_send');

        $reward = $this->modelUser->rewardPoints($getValue,$pcount);
        //积分 = 金额 * 比例
        $scoreTotal = $reward / $score_bl['0'];
        $result = $this->modelUser->spcmoneyAdd($userInfo,$sendValue,$inviteUser,$score_bl[0],$scoreTotal);

        $res = $this->modelUser->yqlistAdd($userInfo,$inviteUser,$scoreTotal);
        if($result && $res){
            return CodeBase::$success;
        }else{
            return CodeBase::$error;
        }
    }

    /**
     * 帮助问题列表
     */
    public function helpList()
    {
        $data['hotList'] = $this->logicHelp->getHelpList(['if_hot'=>1,'status'=>1],'id,name','create_time desc',false);
        $data['type'] = parse_config_array('help_gethelp');
        return $data;
    }

    /**
     * 根据type值 判断
     * 根据id值 查询
     */
    public function helpDetail($data = [])
    {
        if($data['type'] == 1){
            //分类id查询标题
            $type = parse_config_array('help_gethelp');
            $typeList['typeName'] = $type[$data['id']];

            $helpList = $this->logicHelp->getHelpList(['catid'=>$data['id'],'status'=>1],'id,name','create_time desc',false);
            $list = array_merge($typeList,$helpList);
        }else{
            //标题id查询详情内容

            $list = $this->logicHelp->getHelpInfo(['id'=>$data['id']],'name,content');
        }
        return $list;
    }

    /**
     * 反馈
     */
    public function feedback($data = [])
    {
        $userInfo = get_member_by_token($data['user_token']);

        $type = parse_config_array('help_gethelp');

        $picData = $this->loginFile->pictureUpload();
        $data['img_ids'] = $picData['id'];
        $result = $this->modelFeedback->setInfo([
            'catid' => $data['catid'],
            'name' => $type[$data['catid']],
            'content' => $data['content'],
            'userid' => $userInfo->user_id,
            'img_ids' =>$picData['id'],
            'contact' => $data['contact']
        ]);
        $result ? $result : CodeBase::$error;
    }

    /**
     * 我的反馈
     */
    public function feedbackList($data = [])
    {
        $userInfo = get_member_by_token($data['user_token']);
        $this->modelFeedback->alias('f');
        $join = [
            [SYS_DB_PREFIX . 'picture p', 'f.img_ids = p.id', 'LEFT'],
        ];
        $where['f.' . DATA_STATUS_NAME] = ['neq', DATA_DELETE];

        $this->modelFeedback->join = $join;

        return $this->modelFeedback->getList(['f.userid'=>$userInfo->user_id,'f.status'=>1], 'f.name,content,contact,p.name as p_name,path', 'f.create_time desc', false);
    }

    //签到情况
    function signin($data = []){
        $longsign=0;//连续签到天数
        $userInfo = get_member_by_token($data['user_token']);
        $userid=$userInfo->user_id;
        //查询当前用户最新签到情况
        $info=$this->modelSignin->getInfo(['userid'=>$userid,'status'=>DATA_NORMAL],'*');
        if(!empty($info)){
           if($info['longday']==7){
               $longsign=0;
           }else{
               if(date("Y-m-d",strtotime("-1 day"))==date("Y-m-d",strtotime($info['create_time']))){
                   $longsign=$info['longday'];
               }else{
                   $longsign=0;
               }
           }

        }
        //查询该用户当天是否签到
        $insign=$this->issign($userid);//当天是否签到
        $rinfo['insign']=$insign;
        $rinfo['longsign']=$longsign;
        $rinfo['score_signin']=parse_config_array('score_signin');
        return $rinfo;
    }
    //判断当天是否签到
    function issign($userid){
        $insign=0;
        $start = strtotime(date('Y-m-d 00:00:00'));
        $end = time();
        $where['create_time'] = array('between',"$start,$end");
        $where['userid'] = ['=',$userid];
        $where['status'] = ['=',DATA_NORMAL];
        $todaySign=Db::name('signin')->where($where)->find();
        !empty($todaySign) && $insign=1;
        return $insign;
    }
    //去签到
    function gosignin($data = []){
        $score_signin=parse_config_array('score_signin');;
        $userInfo = get_member_by_token($data['user_token']);
        $userid=$userInfo->user_id;
        //查询该用户当天是否签到
        $insign=$this->issign($userid);
        if($insign==1){
            return CodeBase::$userSign;
        }

        //查询当前用户最新签到情况
        $info=$this->modelSignin->getList(['userid'=>$userid,'status'=>DATA_NORMAL],'*','id desc',0);
        if(empty($info[0])){
            //新用户签到，签到天数第一天开始，连续天数为0
            $score=$score_signin[1];
            $res=$this->modelSignin->setInfo(['userid'=>$userid,'status'=>DATA_NORMAL,'longday'=>1,'score'=>$score_signin[1],'create_time'=>time()]);
        }else{
            //前几天已有签到
            //判断是否连续（当前日期减去一天是否与昨天时间是否相等）
            if(date("Y-m-d",strtotime("-1 day"))==date("Y-m-d",strtotime($info[0]['create_time']))){
                //时间相等是连续签到,直接赠送金币
                $longday=$info[0]['longday']+1;
                //判断当前连续天数是否大于7天
                if($info[0]['longday']>=7){
                    $longday=1;
                }
                $score=$score_signin[$longday];
                $res=$this->modelSignin->setInfo(['userid'=>$userid,'status'=>DATA_NORMAL,'longday'=>$longday,'score'=>$score_signin[$longday],'create_time'=>time()]);
            }else{
                //非连续签到从0开始
                $score=$score_signin[1];
                $res=$this->modelSignin->setInfo(['userid'=>$userid,'status'=>DATA_NORMAL,'longday'=>1,'score'=>$score_signin[1],'create_time'=>time()]);
            }

        }
        $handle_text='签到';
        user_log($handle_text, '用户' . $handle_text . '赠送金币，score：' . $score,$userid);
        $result=$this->modelScore->setInfo(['userid'=>$userid,'status'=>DATA_NORMAL,'score'=>$score,'type'=>1,'remark'=>'签到送金币','create_time'=>time()]);
        return $res;
    }

    /**
     * 调查问卷
     */
    public function questionList($data = [])
    {
        $userInfo = get_member_by_token($data['user_token']);

        $queClassInfo = $this->modelQuestionclass->getInfo(['if_new'=>1,'status'=>1],'id,name,remark');

        $queLog = $this->modelQuestionLog->getInfo(['userid'=>$userInfo->user_id,'questionclassid'=>$queClassInfo['id']]);
        if(!$queLog) return '您已参加过此次问卷调查';

        if(!$queClassInfo) return '暂无问卷调查';

        $queInfo = $this->modelQuestion->getList(['questionclassid'=>$queClassInfo['id'],'status'=>1],'id,name,questiontype','sort desc');
        $list = [];
        $list['questionclass_id'] = $queClassInfo['id'];
        $list['name'] = $queClassInfo['name'];
        $list['remark'] = $queClassInfo['remark'];
        foreach($queInfo as $k=>$val){

            $data = $this->modelQuestionSel->getList(['questionid' => $val['id'],'status'=>1],'id,title,img','sort desc');
            $list['data'][] = [
                'question_id' => $val['id'],
                'q_name' => $val['name'],
                'questiontype' => $val['questiontype'],
            ];
            foreach($data as &$v){
                $img = $v['img'] ? get_picture_url($v['img']) : '';
                $list['data'][$k][] = [
                    'id' => $v['id'],
                    'title' => $v['title'],
                    'img' => $img
                ];
            }

        }
        return $list;

    }

    /**
     * 调查问卷提交
     */
    public function questionPost($data = [])
    {
        $userInfo = get_member_by_token($data['user_token']);
        $queClassInfo = $this->modelQuestionclass->getInfo(['id'=>$data['questionclass_id']]);
        if($queClassInfo) return '操作有误';
        Db::startTrans();
        try{
            foreach($data['ids'] as $k=>$val){
                $this->modelQuestionResult->setInfo([
                    'questionclassid' => $data['questionclass_id'],
                    'questionid'=>$val,
                    'answer' => $data['answer'][$k],
                    'userid' =>$userInfo->user_id,
                    'create_time' => time()
                ]);
            }
            $this->modelQuestionLog->setInfo([
                'questionclassid' => $data['questionclass_id'],
                'userid' => $userInfo->user_id,
            ]);
            //用户表用户家积分
            $this->modelUser->where(['userid' => $userInfo->user_id])->setInc('score',$queClassInfo['score']);
            //积分比例
            $score_bl = parse_config_array('score_bl');
            //积分记录表
            $this->modelScore->setInfo([
                'userid' => $userInfo->user_id,
                'type' => 1,
                'status' => 1,
                'remark' => '做调查问卷所获得积分'.$queClassInfo['score'],
                'score' => $queClassInfo['score'],
                'money' => $queClassInfo['score'] / $score_bl['0'],
            ]);
            user_log('提交调查问卷', '用户' . $userInfo->user_id . '赠送金币，score：' . $queClassInfo['score'],$userInfo->user_id);
            Db::commit();
            return true;
        }catch (\Exception $e){
            Db::rollback();
//            return $e->getMessage();
            return CodeBase::$error;
        }

    }

    /**
     * 获取用户信息
     */
    public function userInfo($data = [])
    {
        $userInfo = get_member_by_token($data['user_token']);
        $list = $this->modelUser->getInfo(['userid'=>$userInfo->user_id]);
        unset($list['pwd']);
        return $list;
    }
    //判断今日收益是否产生专属送金币活动
    public function ifspcmoney($userid)
    {
        //今日收益金币
        $post = [
            'type' => 1,
            'userid' => $userid,
            'status' => ['<>', 0],
        ];
        $uinfo = $this->modelUser->getInfo(['userid' => $userid], '*');
        $needscore = parse_config_array('txconfig_needscore');
        $todayScore = Score::where($post)->whereTime('create_time', 'today')->sum('score');
        empty($todayScore) && $todayScore = 0;
        //判断当天是否超过1500
        if ($todayScore >= $needscore[0]) {
            //判断当天是否为已标记日期
            $nowdate = date('Y-m-d', time());
            if ($nowdate == $uinfo['getcount_date']) {
                //今日已标记直接跳过
                return false;
            } else {
                //没标记，判断昨天是否标记
                if (date("Y-m-d", strtotime("-1 day")) == $uinfo['getcount_date']) {
                    //昨天已标记，计入连续天数并将时间标记位改为今天
                    $getcount=($uinfo['getcount'] + 1);
                } else {
                    //昨天未标记，连续获取金币天数断层，时间标记位改为今天并连续天数记1天
                    $getcount=1;
                    //未提现的专属活动活跃归零
                    Db::name('Spcmoney')->where(['userid' => $userid,'type' => [['=',1],['=',3],'or'],'status' => 1,'cantixian'=>0])->update(['day'=>0]);

                }
                $this->modelUser->setInfo(['userid' => $userid, 'getcount' => $getcount, 'getcount_date' => date('Y-m-d', time())]);
                $this->setaddday($this->modelspcmoney->getInfo(['type' => 1, 'userid' => $userid, 'status' => 1]), $userid, 1);
                $this->setaddday($this->modelspcmoney->getInfo(['type' => 3, 'userid' => $userid, 'status' => 1]), $userid, 3);


            }

        } else {
            return false;

        }
    }

    //专属提现活动执行天数增加
    public function setaddday($spcinfo, $userid, $type)
    {
        if ($type == 1) {
            $day = 5;
        } else {
            $day = 10;
        }
        if (!empty($spcinfo)) {
            //是否可提现
            if ($spcinfo['cantixian'] != 1) {
                $addday = $spcinfo['day'] + 1;
                $data['userid'] = $userid;
                if ($addday >= $day) {
                    $data['day'] = $day;
                    $data['cantixian'] = 1;
                } else {
                    $data['day'] = $addday;
                }
                Db::name('Spcmoney')->where(['userid' => $userid, 'type' => $type, 'status' => 1])->update($data);
            }
        }
    }

    //定时任务（每天0点执行）
    public function timed()
    {
        //今日已完成任务数待领取金币+已领取清空


        //判断当前日期-1是否为连续获取金币的日期，不是则连续活跃天数断层 getcount=0 getcount_date=''，未提现的两条记录天数归零
    }

}
