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

use app\api\error\Common as CommonError;
use \Firebase\JWT\JWT;
use think\Db;
use app\common\logic\User as CommonUser;

/**
 * 接口基础逻辑
 */
class Common extends ApiBase
{

    public static $commonUserLogic = null;

    /**
     * 基类初始化
     */
    public function __construct()
    {
        // 执行父类构造方法
        parent::__construct();

        empty(static::$commonUserLogic) && static::$commonUserLogic = get_sington_object('User', CommonUser::class);
    }


    /**
     * 微信登录接口逻辑
     */

    public function wxLogin($data = [])
    {

        $user = static::$commonUserLogic->getUserInfo(['openid' => $data['openid']]);

        if(empty($user)){
            $data = [
                'openid' => $data['openid'],
                'create_time' => time(),
            ];
            $ids = Db::name('user')->insertGetId($data);

            $user = static::$commonUserLogic->getUserInfo(['userid' => $ids]);
        }
        return $this->tokenSign($user);
    }


    /**
     * 手机号登录接口逻辑
     */

    public function login($data = [])
    {

        $validate_result = $this->validateUser->scene('login')->check($data);
        if (!$validate_result) {
            return CommonError::$phoneCodeEmpty;
        }

        begin:

        $user = static::$commonUserLogic->getUserInfo(['phone' => $data['phone']]);
        // 若存在该手机号
        if ($user)
        {
            return CommonError::$phoneFail;

            goto begin;
        }
        //根据code_id查询验证码
        $code = '123456';

        if ($data['code'] !== $code) {

            return CommonError::$codewordError;
        }

        $list = [
            'userid' => $data['user_id'],
            'phone' => $data['phone'],
        ];
        $this->logicUser->setInfo($list);

        return $this->tokenSign($user);
    }


    /**
     * JWT验签方法
     */
    public static function tokenSign($user)
    {

        $key = API_KEY . JWT_KEY;

        $jwt_data = ['user_id' => $user['userid'], 'phone' => $user['phone'], 'name' => $user['name'], 'create_time' => $user['create_time']];

        $token = [
            "iss"   => "OneBase JWT",         // 签发者
            "iat"   => TIME_NOW,              // 签发时间
            "exp"   => TIME_NOW + TIME_NOW,   // 过期时间
            "aud"   => 'OneBase',             // 接收方
            "sub"   => 'OneBase',             // 面向的用户
            "data"  => $jwt_data
        ];

        $jwt = JWT::encode($token, $key);

        $jwt_data['user_token'] = $jwt;

        return $jwt_data;
    }

    /**
     * 友情链接
     */
    public function getBlogrollList()
    {

        return $this->modelBlogroll->getList([DATA_STATUS_NAME => DATA_NORMAL], true, 'sort desc,id asc', false);
    }
}
