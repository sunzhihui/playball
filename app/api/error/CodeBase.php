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

class CodeBase
{
    
    public static $success              = [API_CODE_NAME => 0,         API_MSG_NAME => '操作成功'];

    public static $error           = [API_CODE_NAME => 1000000,         API_MSG_NAME => '操作失败'];

    public static $accessTokenError     = [API_CODE_NAME => 1000001,   API_MSG_NAME => '访问Toekn错误'];
    
    public static $userTokenNull        = [API_CODE_NAME => 1000002,   API_MSG_NAME => '用户Toekn不能为空'];
    
    public static $apiUrlError          = [API_CODE_NAME => 1000003,   API_MSG_NAME => '接口路径错误'];
    
    public static $dataSignError        = [API_CODE_NAME => 1000004,   API_MSG_NAME => '数据签名错误'];
    
    public static $userTokenError       = [API_CODE_NAME => 1000005,   API_MSG_NAME => '用户Toekn解析错误'];
    public static $userLogError       = [API_CODE_NAME => 1000006,   API_MSG_NAME => '用户日志生成错误'];
    public static $emptyId              = [API_CODE_NAME => 2000001,   API_MSG_NAME => 'ID不能为空'];
    public static $emptyItem              = [API_CODE_NAME => 2000002,   API_MSG_NAME => '数据查询结果不存在'];


    public static $statusError              = [API_CODE_NAME => 1000010,   API_MSG_NAME => '账户已禁用'];

    public static $userSign              = [API_CODE_NAME => 3000001,   API_MSG_NAME => '用户已签到'];


}
