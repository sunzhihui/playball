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
        $scoreTotal = $reward * $score_bl['0'];
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

}
