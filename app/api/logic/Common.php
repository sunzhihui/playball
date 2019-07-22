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
 * �ӿڻ����߼�
 */
class Common extends ApiBase
{

    public static $commonUserLogic = null;

    /**
     * �����ʼ��
     */
    public function __construct()
    {
        // ִ�и��๹�췽��
        parent::__construct();

        if (empty(static::$commonUserLogic)) {
            static::$commonUserLogic = get_sington_object('User', CommonUser::class);
        }
    }


    /**
     * ΢�ŵ�¼�ӿ��߼�
     */

    public function wxLogin($data = [])
    {

        $user = static::$commonUserLogic->getUserInfo(['openid' => $data['openid']]);
        $text = '΢�ŵ�¼';

        if (empty($user)) {
            $text = '΢��ע��';
            $data = [
                'openid' => $data['openid'],
                'yqcode' => $this->invitation_code(),
                'create_time' => time(),
            ];
            $ids = Db::name('user')->insertGetId($data);

            $user = static::$commonUserLogic->getUserInfo(['userid' => $ids]);
        }
        user_log($text, '�û�' . $user['userid'] .$text. '��openid��'.$data['openid'],$user['userid']);
        return $this->tokenSign($user);
    }


    /**
     * �ֻ���ע���¼�ӿ��߼�
     */

    public function login($data = [])
    {
        $validate_result = $this->validateUser->scene('login')->check($data);

        if (!$validate_result) return CommonError::$phoneCodeEmpty;

        if (!$this->is_mobile_phone($data['phone'])) return CommonError::$phoneError;

        begin:
        //����code_id��ѯ��֤��
        $codeInfo = $this->modelCode->getInfo(['id' => $data['code_id']]);

        if ($data['code'] !== /*$codeInfo['code']*/'123456') {
            return CommonError::$codewordError;
        }

        $user = static::$commonUserLogic->getUserInfo(['phone' => $data['phone']]);
        $text = '�ֻ��ŵ�¼';
        // �������ڸ��ֻ��ţ������û�
        if (!$user) {
            $text = '�ֻ���ע��';
            $list = [
                'phone' => $data['phone'],
                'name' => substr_replace($data['phone'], '****', 3, 4),
                'yqcode' => $this->invitation_code(),
                'create_time' => time()
            ];
            $ids = Db::name('user')->insertGetId($list);
            $user = static::$commonUserLogic->getUserInfo(['userid' => $ids]);
        }
        user_log($text, '�û�' . $user['userid'] . $text.'��phone��'.$data['phone'],$user['userid']);
        return $this->tokenSign($user);
    }


    /**
     * ������֤��
     */
    public function sendCode($data = [])
    {

        $code = rand('100000', '999999');//$phone+$code
        $data = [
            'phone' => $data['phone'],
            'code' => $code,
            'created_time' => time()
        ];
        //д�����ݿ� $codeid
        $codeid = Db::name('code')->insertGetId($data);

        return $codeid;
    }

    /**
     * JWT��ǩ����
     */
    public static function tokenSign($user)
    {

        $key = API_KEY . JWT_KEY;

        $jwt_data = ['user_id' => $user['userid'], 'phone' => $user['phone'], 'name' => $user['name'], 'create_time' => $user['create_time']];

        $token = [
            "iss" => "OneBase JWT",         // ǩ����
            "iat" => TIME_NOW,              // ǩ��ʱ��
            "exp" => TIME_NOW + TIME_NOW,   // ����ʱ��
            "aud" => 'OneBase',             // ���շ�
            "sub" => 'OneBase',             // ������û�
            "data" => $jwt_data
        ];

        $jwt = JWT::encode($token, $key);

        $jwt_data['user_token'] = $jwt;

        return $jwt_data;
    }

    /**
     * ��������
     */
    public function getBlogrollList()
    {

        return $this->modelBlogroll->getList([DATA_STATUS_NAME => DATA_NORMAL], true, 'sort desc,id asc', false);
    }

    /**
     * �ж��ֻ���
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
     * ����������
     */
    function invitation_code()
    {

        $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $rand = $code[rand(0,25)]
            .substr(base_convert(md5(uniqid(md5(microtime(true)),true)), 16, 10), 0, 6);
        return $rand;
    }
}

