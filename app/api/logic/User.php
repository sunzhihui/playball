<?php

namespace app\api\logic;

use app\api\error\Common as CommonError;
use app\api\error\CodeBase;
use app\api\logic\Common as CommonApi;
use app\common\logic\File as CommonFile;
use app\common\model\Score;
use think\Db;

class User extends ApiBase
{

    public static $commonFileLogic = null;

    /**
     * 基类初始化
     */
    public function __construct()
    {
        // 执行父类构造方法
        parent::__construct();

        if(empty(static::$commonFileLogic)){
            static::$commonFileLogic = get_sington_object('File', CommonFile::class);
        }

    }
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

        $user = $this->modelUser->getInfo(['phone' => $data['phone']]);

        if ($user) return CommonError::$phoneExist;

        $list = [
            'userid' => $userInfo->user_id,
            'phone' => $data['phone'],
            'name' => substr_replace($data['phone'], '****', 3, 4),
        ];

        $result = $this->logicUser->setInfo($list);
        $result && user_log('绑定手机号', '用户' . $userInfo->user_id . '绑定手机号'.$data['phone'],$userInfo->user_id );
        return $result ? $data['phone'] : CommonError::$setPhoneFail;
    }

    /**
     * 我的钱包
     */
    public function wallet($data = [])
    {
        $type = $data['type'] ? $data['type'] : 1;
        $status = $data['type'] == 1 ? 2 : 0;

        $userInfo = get_member_by_token($data['user_token']);

        if($type == 1){
            //收益明细展示3日记录
            $time = strtotime(date("Y-m-d", strtotime("-3 days")));

            $map = [
                'create_time'=> ['>=', $time],
                'userid' => ['=', $userInfo->user_id],
            ];
            $list = Db::name('score')->where($map)->where(['type'=>1])->field(['score','remark','create_time',])->order('create_time desc')->select();
            $arr['list'] = $this->modelUser->groupVisit($list,$type,$status,$userInfo->user_id);
        }else{
            //支出记录展示半年
//            $time = 1556150400;
            $old_time = strtotime('-5 month',time());
            for($i = 0;$i <= 5; ++$i){
                $t = strtotime("+$i month",$old_time);
                if(date('Y') == date('Y',$t)) $m = date('m',$t);
                else $m = date('Y-m',$t);
                $date[$m] = explode('/',date('Y-m-01',$t).'/'.date('Y-m-',$t).date('t',$t));
            }
            $arr['list'] = $this->modelUser->pay($date);
        }
        //今日收益金币
        $arr['todayScore'] = Score::where(['type' => 1,'userid' => $userInfo->user_id,'status' => 1])->whereTime('create_time','today')->sum('score');
        //累计收益金币
        $arr['totalScore'] = Score::where(['type' => 1,'userid' => $userInfo->user_id,'status'=>1])->sum('score');
        //累计支出金币

        $arr['outScore'] = Score::where(['type' => ['=', 2],'status' => ['<>' ,2],'userid' => $userInfo->user_id,])->sum('score');

        $userData = $this->modelUser->getInfo(['userid' => $userInfo->user_id],'score');

        $arr['useableScore'] = $userData['score'];
        //积分比例
        $score_bl = parse_config_array('score_bl');
        //汇率
        $arr['exchangeRate'] = $score_bl['0'];
        $arr['useableMoney'] = $arr['useableScore'] * $score_bl['0'];
        return $arr;
    }

    /**
     * 支出明细待审核/拒绝的详细信息
     */
    public function walletInfo($data = [])
    {
        $list = $this->modelScore->getInfo(['scoreid'=>$data['id']],'score,paytype,tx_orderno,create_time,update_time'); 
        return $list;
    }

    /**
     * 邀请好友信息
     */
    public function inviteInfo($data = [])
    {

        $userInfo = get_member_by_token($data['user_token']);
        //积分比例
        $score_bl = parse_config_array('score_bl');
        if($data['type'] == 1){
            //邀请好友信息
            //奖励发放规则
            $list['sendValue'] = parse_config_array('yqconfig_send');
            $list['getValue'] = parse_config_array('yqconfig_get');
            $userArr = $this->modelUser->getInfo(['userid' => $userInfo->user_id],'yqcode');
            $list['yqcode'] = $userArr['yqcode'];
            //有效好友
            $list['inviteFriend'] = $this->modelUser->where(['pid'=>$userInfo->user_id,'status'=>1])->count();
            //已到账收益
            $list['income'] = $this->modelYqmoney->where(['userid'=>$userInfo->user_id,'status'=>1,'ifsend'=>1])->sum('score') / $score_bl['0'];
            //预计收益
            $list['expectedReturn'] = $this->modelYqmoney->where(['userid'=>$userInfo->user_id,'status'=>0,'ifsend'=>0])->sum('score') / $score_bl['0'];
            //快速提现金额
            $list['money'] = $this->modelSpcmoney->where(['userid'=>$userInfo->user_id,'status'=>1,'cantixian'=>1])->sum('score') / $score_bl['0'];
        }else{
            //邀请收益
            $this->modelYqmoney->alias('y');
            $join = [
                [SYS_DB_PREFIX . 'user u', 'y.cid = u.userid', 'LEFT'],
            ];
            $arr = $this->modelYqmoney
                ->join($join)
                ->where(['y.userid'=>$userInfo->user_id,'y.status'=>1,'y.ifsend'=>1])
                ->field('u.name,y.paydate,yqlistid,sum(y.score) as income')
                ->group('y.cid')->select();

            foreach($arr as $k=>$val){
                $scoreAll = $this->modelYqlist->getInfo(['id'=>$val['yqlistid']],'score');
                $expectedReturn = $scoreAll['score'] - $val['income'];
                $arr[$k]['income'] = $val['income'] / $score_bl['0'];
                $arr[$k]['expectedReturn'] = $expectedReturn / $score_bl['0'];
            }
            $list = $arr;
        }
        return $list;
    }

    /**
     * 邀请好友
     */
    public function invite($data = [])
    {
        $userInfo = get_member_by_token($data['user_token']);
        $userList = $this->modelUser->getInfo(['userid' => $userInfo->user_id],'pid,userid');

        if($userList['pid']) return CommonError::$pidError;

        if($userList['userid'] == $userInfo->user_id) return CommonError::$inviteError;
        //邀请人信息
        $inviteUser = $this->modelUser->getInfo(['yqcode' => $data['code']],'score,userid');
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
        $scoreTotal = $reward * $score_bl['0'];
        $result = $this->modelUser->spcmoneyAdd($userInfo,$sendValue,$inviteUser,$score_bl[0],$scoreTotal);

        if($result ){
            //判断今日收益是否产生专属送金币活动
            $this->ifspcmoney($inviteUser['userid']);
            user_log('邀请好友', '用户邀请好友'.$userInfo->name.'，获得金币score：' . $scoreTotal,$inviteUser['userid']);
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

        $picData = $this->loginFile->pictureUpload('img');
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
        $userInfo=$this->userInfo($data);
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
                //                //时间相等是连续签到,直接赠送金币
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
        $this->modelUser->setInfo(['score' => ($userInfo['score'] + $score)], ['userid' => $userid]);
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
        if(!$queClassInfo) return CommonError::$questionError;

        if(!$queLog) return CommonError::$completeError;

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
        if(!$queClassInfo) return CommonError::$questionErrorss;
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
            //用户表用户加积分,加余额
            $userData = $this->modelUser->getInfo(['userid' => $userInfo->user_id],'score');

            //增加用户积分余额
            $this->modelUser->setInfo([
                'userid' => $userInfo->user_id,
                'score' => $userData['score'] + $queClassInfo['score'],
            ]);
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
            $this->ifspcmoney($userInfo->user_id);
            user_log('提交调查问卷', '用户参与问卷调查，赠送金币，score：' . $queClassInfo['score'],$userInfo->user_id);
            Db::commit();
            return true;
        }catch (\Exception $e){
            Db::rollback();
            return CodeBase::$error;
        }

    }

    /**
     * 获取用户信息
     */
    public function userInfo($data = [],$flid=true)
    {
        if(!empty($data['userid'])){
            $userid=$data['userid'];
        }else{
            $userInfo = get_member_by_token($data['user_token']);
            $userid=$userInfo->user_id;
        }

        $list = $this->modelUser->getInfo(['userid'=>$userid],$flid);
        //积分比例
        if($list){
            $score_bl = parse_config_array('score_bl');
            $list['money']=($list['score']/$score_bl[0])?:0;
            $list['score_bl']=$score_bl[0];
            //今日收益金币
            $post = [
                'type' => 1,
                'userid' => $userid,
                'status' => 1
            ];
            $list['todayScore'] = (Score::where($post)->whereTime('create_time','today')->sum('score'))?:0;
        }else{
            return CommonError::$emptyUser;
        }

        unset($list['pwd']);
        return $list;
    }

    /**
     * 绑定支付宝/微信账号
     */
    public function bindNumber($data = [])
    {
        $userInfo = get_member_by_token($data['user_token']);

        $userData = $this->modelUser->getInfo(['userid' => $userInfo->user_id]);
        //绑定支付宝账号
        if($data['type'] == 1){
            if($userData['zfbname'] && $userData['zfbnum']) return CommonError::$zfbError;

            $result = $this->modelUser->setInfo([
                'userid' => $userInfo->user_id,
                'zfbname' => $data['zfbname'],
                'zfbnum' => $data['zfbnum'],
            ]);
            $text = '绑定支付宝';
            $msg = ',zfbname:'.$data['zfbname'].',zfbnum:'.$data['zfbnum'];
        }elseif($data['type'] == 2){
            //绑定微信账号
            if($userData['openid']) return CommonError::$wxError;
            $openIdInfo = $this->modelUser->getInfo(['openid'=>$data['openid']]);
            if($openIdInfo) return $this->apiReturn([API_CODE_NAME => 100002, API_MSG_NAME => '授权失败，该微信号已授权其他账号'.$openIdInfo['name']],'','');

            $result = $this->modelUser->setInfo([
                'userid' => $userInfo->user_id,
                'openid' => $data['openid'],
            ]);
            $text = '绑定微信';
            $msg = ',openid:'.$data['openid'];
        }
        $result && user_log($text, '用户' .$text.$msg,$userInfo->user_id);
        $result ? true : CodeBase::$error;
    }

    /**
     * 实名认证
     */
    public function verified($data = [])
    {
        $userInfo = get_member_by_token($data['user_token']);

        $userData = $this->modelUser->getInfo(['userid' => $userInfo->user_id]);

        if($userData['ifmanager'] == 1) return CommonError::$verifiedError;
        if($userData['ifmanager'] == 3) return CommonError::$verifiedCheckError;
        $post = [
            'userid' => $userInfo->user_id,
            'card' => $data['card'],
            'ifmanager' => 3,
            'cardname' => $data['cardname'],
        ];
        if($data['type'] == 2){
            $picData1 = static::$commonFileLogic->pictureUpload('card_img1');
            $picData2 = static::$commonFileLogic->pictureUpload('card_img2');
            $arr = [
                'card_img1' => $picData1['id'],
                'card_img2' => $picData2['id']
            ];
            $post = array_merge($post,$arr);
        }

        $result = $this->modelUser->setInfo($post);

        $result && user_log('实名认证', '用户进行实名认证，cardname：'.$data['cardname'].',card:'.$data['card'],$userInfo->user_id);
        $result ? true : CodeBase::$error;
    }

    //判断今日收益是否产生可提现专属送金币活动
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

        //邀请好友的金币按天发放
    }


    public function percenter($data=[]){
        $userInfo = $this->userInfo($data);
        //是否注册专属活动
        if ($userInfo['if_havespc'] != 1) {
            $this->newpeople_init($data);
        }
        //动态
        $money_active=[];
        //type=0不跳转  type=1 提现页面 type=2 去签到 type=3 游戏中心 type=4首页
        //1.是否有待审核金币
        $checkscore=Db::name('score')->where(['userid'=>$userInfo['userid'],'status'=>0,'type'=>[['=',2],['=',3],'or']])->find();
        if($checkscore){
            $arr['msg']='您'.date('Y-m-d',$checkscore['create_time']).'有'.$checkscore['remark'].'提现申请待审核';
            $arr['type']=1;
            $money_active[]=$arr;
        }
        getOrderON();
        //2.今日是否获得最新的金币（a.今日是否获得最新的金币 b）
        if($userInfo['todayScore']>0){
            //今日收益占所有用户的比例
            //今日收益小于自己的人数
            $start = strtotime(date('Y-m-d 00:00:00'));
            $end = time();
            $sql="select count(1) as count from (SELECT sum(score) as scores,userid FROM `yb_score` where type=1 and create_time>=$start and create_time<=$end group by userid) a where a.scores<=20";
            $minScorecount=Score::query("$sql");
            $minScorecount[0]['count']?:$minScorecount[0]['count']=0;
            //今日所有收益过的人数
            $Scorecount = (Score::where(['type' => 1, 'status' => 1])->group('userid')->whereTime('create_time','today')->count());
            $daymoneybl=round($minScorecount[0]['count']/$Scorecount,2)*100;

            $arr['msg']='今日已赚'.$userInfo['todayScore'].'金币，已超过'.$daymoneybl.'%的人!去赚更多';
            $arr['type']=3;
            $money_active[]=$arr;
        }else{
            //今日还未获得金币，去签到
            $arr['msg']='您今日未获得任何金币，去签到赚钱';
            $arr['type']=4;
            $money_active[]=$arr;
        }
        //3.今日是否签到
        $issign=$this->issign($userInfo['userid']);
        if($issign!=1){
            //提示去签到
            $arr['msg']='您今日未签到，去签到赚钱';
            $arr['type']=4;
            $money_active[]=$arr;
        }
        $res['userInfo']=$userInfo;
        $res['money_active']=$money_active;
        $res['adv'] = $this->logicHome->getAdvList(['ifmy'=>1]);
        $score_newzc=parse_config_array('score_newzc');
        $score_newtask=parse_config_array('score_newtask');
        $score_newread=parse_config_array('score_newread');
        $score_newtx=parse_config_array('score_newtx');
        $newtxinfo=$this->modelSpcmoney->getInfo(['userid'=>$userInfo['userid'],'type'=>4,'status'=>1]);
        $iftx=0;
        !empty($newtxinfo) && $iftx=$newtxinfo['cantixian'];
        //throw_response_exception($userInfo);
        $res['newpeopletask']=[
            ['score'=>$score_newzc[0],'ifget'=>1,'title'=>'注册奖励','type'=>'1'],
            ['score'=>$score_newread[0],'ifget'=>$userInfo['if_read'],'title'=>'看新闻得金币','type'=>'2'],
            ['score'=>$score_newtask[0],'ifget'=>$userInfo['if_task'],'title'=>'做任务开1个宝箱','type'=>'3'],
            ['score'=>$score_newtx[0],'ifget'=>$iftx,'title'=>'1元提现新人专享','type'=>'4'],
        ];

        return $res ? $res : CodeBase::$error;;
    }
    //用户提现初始化界面
    public function tixian_init($data=[],$filed=''){
        $userInfo = $this->userInfo($data);
        $res['userInfo']=$userInfo;
        $this->modelSpcmoney->group('type');
        $res['spc']=$this->modelSpcmoney->getList(['userid'=>$userInfo['userid'],'cantixian'=>1,'status'=>1],$filed,'',false);
        //普通提现配置
        $res['txconfig_get']=parse_config_array('txconfig_get');
        return $res;
    }
    //用户申请提现
    public function tixian_apply($data=[]){
        $this->newpeople_init($data);
        $userInfo = $this->userInfo($data);
        $userid=$userInfo['userid'];
        //
        if($data['txtype']==1){
            //专属提现
            $spcmoney=$this->modelSpcmoney->getInfo(['id'=>$data['score'],'userid'=>$userid]);
            empty($spcmoney) && $this->apiError(CommonError::$emptyItem);
            $score=$spcmoney['score'];
            $pid=$spcmoney['id'];
            $type=3;
            $remark='专属活动提现';
        }else{
            //普通提现
            //金币余额修改，添加日志，金币表添加提现记录
            $score=$data['score'];
            $pid='';
            $type=2;
            $remark='金币提现';
        }
        Db::startTrans();
        try{
            !($userInfo['score']-$score>=0) && $this->apiError(CommonError::$emptyscore);
            //金币余额修改，添加日志，金币表添加提现记录
            $this->modelScore->setInfo(['score'=>$score,'userid'=>$userid,'remark'=>$remark,'status'=>0,'pid'=>$pid,'paytype'=>$data['type'],'type'=>$type,'tx_orderno'=>random(11)]);
            //修改余额
            $this->modelUser->setInfo(['score'=>($userInfo['score']-$score)],['userid'=>$userid]);
            //状态改为不可提现
            if($pid){
                $this->modelSpcmoney->setInfo(['cantixian'=>0],['id'=>$pid]);
            }
            user_log($remark, $userInfo['name'].'申请'.$remark.$score,$userid);
            Db::commit();
            return true;
        }catch (\Exception $e){
            Db::rollback();
            return CodeBase::$error;
        }

    }

    //新用户注册后添加专属活动，并赠送新用户注册金币
    function newpeople_init($data=[])
    {
        $userInfo = $this->userInfo($data);
        if ($userInfo['if_havespc'] != 1) {
            //先删除用户下面的所有特殊活动
            $this->modelSpcmoney->setInfo(['status' => 0, 'cantixian' => 0], ['userid' => $userInfo['userid'], 'type' => ['neq', 2]]);
            $userid = $userInfo['userid'];
            //添加新的特殊活动
            //连续5天活跃
            $this->modelSpcmoney->setInfo(['status' => 1, 'score' => parse_config_str('txconfig_fiveday'), 'type' => 1, 'userid' => $userid]);
            //连续10天活跃
            $this->modelSpcmoney->setInfo(['status' => 1, 'score' => parse_config_str('txconfig_tenday'), 'type' => 3, 'userid' => $userid]);
            //新用户首次提现1元,可直接提现
            $this->modelSpcmoney->setInfo(['status' => 1, 'cantixian' => 1, 'score' => parse_config_str('txconfig_frist'), 'type' => 4, 'userid' => $userid]);
            //修改用户信息if_havespc等于1
            $remark = '新用户注册送金币';
            $this->modelScore->setInfo(['score' => parse_config_str('score_newzc'), 'userid' => $userInfo['userid'], 'remark' => $remark, 'status' => 1, 'type' => 1]);
            user_log($remark, $userInfo['name'] . $remark . parse_config_str('score_newzc'), $userid);
            //修改余额
            $this->modelUser->setInfo(['if_havespc' => 1, 'score' => ($userInfo['score'] + parse_config_str('score_newzc'))], ['userid' => $userid]);
            return true;
        } else {
            return true;
        }
    } 

}
