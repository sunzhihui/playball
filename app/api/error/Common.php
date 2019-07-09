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

    public static $passwordError            = [API_CODE_NAME => 1010001, API_MSG_NAME => '登录密码错误'];

    public static $codewordError            = [API_CODE_NAME => 1010002, API_MSG_NAME => '验证码错误'];

    public static $phoneError            = [API_CODE_NAME => 1010003, API_MSG_NAME => '手机号格式错误'];

    public static $phoneExist            = [API_CODE_NAME => 1010004, API_MSG_NAME => '手机号已存在'];

    public static $phoneBindError            = [API_CODE_NAME => 1010005, API_MSG_NAME => '已绑定手机号'];

    public static $phoneCodeEmpty           = [API_CODE_NAME => 1010006, API_MSG_NAME => '手机号或验证码不能为空'];

    public static $registerFail             = [API_CODE_NAME => 1010007, API_MSG_NAME => '注册失败'];

    public static $phoneFail                = [API_CODE_NAME => 1010008, API_MSG_NAME => '手机号已使用'];

    public static $setPhoneFail                = [API_CODE_NAME => 1010009, API_MSG_NAME => '手机号绑定失败'];



}
