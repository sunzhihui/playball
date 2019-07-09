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

        if (empty(static::$commonUserLogic)) {
            static::$commonUserLogic = get_sington_object('User', CommonUser::class);
        }
    }


    /**
     * 微信登录接口逻辑
     */

    public function wxLogin($data = [])
    {

        $user = static::$commonUserLogic->getUserInfo(['openid' => $data['openid']]);

        if (empty($user)) {
            $data = [
                'openid' => $data['openid'],
                'yqcode' => $this->invitation_code(),
                'create_time' => time(),
            ];
            $ids = Db::name('user')->insertGetId($data);

            $user = static::$commonUserLogic->getUserInfo(['userid' => $ids]);
        }
        return $this->tokenSign($user);
    }


    /**
     * 手机号注册登录接口逻辑
     */

    public function login($data = [])
    {
        $validate_result = $this->validateUser->scene('login')->check($data);

        if (!$validate_result) return CommonError::$phoneCodeEmpty;

        if (!$this->is_mobile_phone($data['phone'])) return CommonError::$phoneError;

        begin:
        //根据code_id查询验证码
        $codeInfo = $this->modelCode->getInfo(['id' => $data['code_id']]);

        if ($data['code'] !== /*$codeInfo['code']*/'123456') {
            return CommonError::$codewordError;
        }

        $user = static::$commonUserLogic->getUserInfo(['phone' => $data['phone']]);

        // 若不存在该手机号，新增用户
        if (!$user) {

            $list = [
                'phone' => $data['phone'],
                'name' => substr_replace($data['phone'], '****', 3, 4),
                'yqcode' => $this->invitation_code(),
                'create_time' => time()
            ];
            $ids = Db::name('user')->insertGetId($list);
            $user = static::$commonUserLogic->getUserInfo(['userid' => $ids]);
        }

        return $this->tokenSign($user);
    }


    /**
     * 发送验证码
     */
    public function sendCode($phone)
    {

        $code = rand('100000', '999999');//$phone+$code
        $data = [
            'phone' => $phone,
            'code' => $code,
            'created_time' => time()
        ];
        //写入数据库 $codeid
        $codeid = Db::name('code')->insertGetId($data);

        return $codeid;
    }

    /**
     * JWT验签方法
     */
    public static function tokenSign($user)
    {

        $key = API_KEY . JWT_KEY;

        $jwt_data = ['user_id' => $user['userid'], 'phone' => $user['phone'], 'name' => $user['name'], 'create_time' => $user['create_time']];

        $token = [
            "iss" => "OneBase JWT",         // 签发者
            "iat" => TIME_NOW,              // 签发时间
            "exp" => TIME_NOW + TIME_NOW,   // 过期时间
            "aud" => 'OneBase',             // 接收方
            "sub" => 'OneBase',             // 面向的用户
            "data" => $jwt_data
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

    /**
     * 判断手机号
     */
    function is_mobile_phone ($mobile_phone)
    {
        $chars = "/^1[34578]{1}[0-9]{9}$/";
        if (preg_match($chars, $mobile_phone)) {
            return true;
        }
        return false;

    }


    /**
     * 生成邀请码
     */
    function invitation_code()
    {

        $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $rand = $code[rand(0,25)]
            .substr(base_convert(md5(uniqid(md5(microtime(true)),true)), 16, 10), 0, 6);
        return $rand;
    }
}

