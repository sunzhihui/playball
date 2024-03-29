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

namespace app\api\error;

class Common
{

    public static $error                = [API_CODE_NAME => 1010000, API_MSG_NAME => '操作失败'];

    public static $passwordError            = [API_CODE_NAME => 1010001, API_MSG_NAME => '登录密码错误'];

    public static $codewordError            = [API_CODE_NAME => 1010002, API_MSG_NAME => '验证码错误'];

    public static $phoneError            = [API_CODE_NAME => 1010003, API_MSG_NAME => '手机号格式错误'];

    public static $phoneExist            = [API_CODE_NAME => 1010004, API_MSG_NAME => '手机号已存在'];

    public static $phoneBindError            = [API_CODE_NAME => 1010005, API_MSG_NAME => '已绑定手机号'];

    public static $phoneCodeEmpty           = [API_CODE_NAME => 1010006, API_MSG_NAME => '手机号或验证码不能为空'];

    public static $registerFail             = [API_CODE_NAME => 1010007, API_MSG_NAME => '注册失败'];

    public static $phoneFail                = [API_CODE_NAME => 1010008, API_MSG_NAME => '手机号已使用'];

    public static $setPhoneFail                = [API_CODE_NAME => 1010009, API_MSG_NAME => '手机号绑定失败'];

    public static $pidError                = [API_CODE_NAME => 1010011, API_MSG_NAME => '已存在上级'];

    public static $zfbError                = [API_CODE_NAME => 1010012, API_MSG_NAME => '您已绑定支付宝'];
 
    public static $wxError                = [API_CODE_NAME => 1010013, API_MSG_NAME => '您已绑定微信'];

    public static $verifiedError                = [API_CODE_NAME => 1010014, API_MSG_NAME => '您已实名认证'];

    public static $verifiedCheckError                = [API_CODE_NAME => 1010015, API_MSG_NAME => '您已提交实名认证申请，审核中~'];

    public static $questionError                = [API_CODE_NAME => 1010016, API_MSG_NAME => '暂无问卷调查'];

    public static $completeError                = [API_CODE_NAME => 1010017, API_MSG_NAME => '您已参加过此次问卷调查'];

    public static $inviteError                = [API_CODE_NAME => 1010018, API_MSG_NAME => '不能填写自己的邀请码'];

    public static $emptyUser                = [API_CODE_NAME => 1010019, API_MSG_NAME => '用户不存在'];
    public static $emptyId              = [API_CODE_NAME => 2000001,   API_MSG_NAME => 'ID不能为空'];
    public static $emptyItem              = [API_CODE_NAME => 2000002,   API_MSG_NAME => '数据查询结果不存在'];
    public static $haveTask              = [API_CODE_NAME => 2000003,   API_MSG_NAME => '子任务已完成，无法继续提交'];





    public static $userSign              = [API_CODE_NAME => 3000001,   API_MSG_NAME => '用户已签到'];


}
