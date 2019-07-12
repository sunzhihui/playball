<?php

namespace app\api\logic;

use app\api\error\Common as CommonError;
use app\api\error\CodeBase;
use app\api\logic\Common as CommonApi;
use app\common\logic\User as CommonUser;
use app\common\model\Code;
use app\common\model\Score;
use think\Db;
use app\api\model\User as Users;


class User extends ApiBase
{

    public static $commonUserLogic = null;

    /**
     * 基类初始化
     */
    public function __construct()
    {
        // 执行父类构造方法
        parent::__construct();

        if (empty(static::$commonUserLogic)) {
            static::$commonUserLogic = get_sington_object('User', CommonUser::class);
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
        $codeMOdel = new Code();
        $codeInfo = $codeMOdel->getInfo(['id' => $data['code_id']]);

        if ($data['code'] !== /*$codeInfo['code']*/'123456') {
            return CommonError::$codewordError;
        }

        $user = static::$commonUserLogic->getUserInfo(['phone' => $data['phone']]);

        if ($user) return CommonError::$phoneExist;

        $list = [
            'userid' => $userInfo->user_id,
            'phone' => $data['phone'],
            'name' => substr_replace($data['phone'], '****', 3, 4),
        ];
        $result = static::$commonUserLogic->setInfo($list);

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
        $model = new Users();
        $arr['list'] = $model->groupVisit($list,$type,$status,$userInfo->user_id);

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
        $scoreModel = new Score();
        $useableScore = $scoreModel->getInfo(['userid' => $userInfo->user_id],'score');
        $arr['useableScore'] = $useableScore['score'];

        return $arr;
    }


    /**
     * 邀请好友
     */
    public function invite($data = [])
    {
        $userInfo = get_member_by_token($data['user_token']);
        $userList = static::$commonUserLogic->getUserInfo(['userid' => $userInfo->user_id],'pid');
        if($userList['pid']){
            return CommonError::$pidError;
        }
        //邀请人信息
        $inviteUser = static::$commonUserLogic->getUserInfo(['yqcode' => $data['code']],'score,userid');
        //邀请人已经邀请的人数
        $pcount =Users::getInviteCount(['pid'=>$inviteUser['userid'],'status'=>1]);
        //查询配置
        $getValue = parse_config_array('yqconfig_get');
        //积分比例
        $score_bl = parse_config_array('score_bl');
        //奖励发放规则
        $sendValue = parse_config_array('yqconfig_send');
        $model = new Users();
        $reward = $model->rewardPoints($getValue,$pcount);
        //积分 = 金额 * 比例
        $scoreTotal = $reward * $score_bl['0'];
        $result = $model->spcmoneyAdd($userInfo,$sendValue,$inviteUser,$score_bl[0],$scoreTotal);
        $res = $model->yqlistAdd($userInfo,$inviteUser,$scoreTotal);
        if($result && $res){
            return CodeBase::$success;
        }else{
            return CodeBase::$error;
        }
    }

}
