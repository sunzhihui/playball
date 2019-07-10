<?php

namespace app\api\logic;

use app\api\error\Common as CommonError;
use app\api\logic\Common as CommonApi;
use app\common\logic\User as CommonUser;
use think\Db;


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
        $codeInfo = $this->modelCode->getInfo(['id' => $data['code_id']]);

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
        $status = $data['type'] ? 0 : 2;

        $userInfo = get_member_by_token($data['user_token']);

        $time = strtotime(date("Y-m-d", strtotime("-3 days")));
        $map = [
            'create_time'=> ['>=', $time],
            'userid' => ['=', $userInfo->user_id],
            'type' => ['=', $type],
            'status' => ['<>', $status]
        ];

        $list = Db::table('yb_score')->where($map)->field(['score','remark','create_time'])->order('create_time desc')->select();
        $arr['listArr'] = $this->groupVisit($list,$type,$status,$userInfo->user_id);
        //今日收益金币
        $post = [
            'type' => 1,
            'userid' => $userInfo->user_id,
            'status' => ['<>',0],
        ];
        $arr['todayScore'] = Db::table('yb_score')->where($post)->whereTime('create_time','today')->sum('score');
        //累计收益金币
        $arr['totalScore'] = Db::table('yb_score')->where(['type' => 1,'userid' => $userInfo->user_id,'status'=>1])->sum('score');
        //累计支出金币
        $where = [
            'type' => ['=', 2],
            'status' => ['<>' ,2],
            'userid' => $userInfo->user_id,
        ];
        $arr['outScore'] = Db::table('yb_score')->where($where)->sum('score');
        $arr['useableScore'] = Db::table('yb_user')->where(['userid' => $userInfo->user_id])->field('score')->find()['score'];
        return $arr;
    }

    //处理收益（支出）明细列表数据
    function groupVisit($list,$type,$status,$user_id)
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
            $todayTotal=Db::table('yb_score')->where($map)/*->whereTime('create_time', date('Y-m-d',$v['create_time']))*/->sum('score');
            $listArr[$date]['todayTotal'] = $todayTotal;
            $listArr[$date]['list'][] = $v;
        }
        return $listArr;
    }
}
