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
    
    public static $success              = [API_CODE_NAME => 0,         API_MSG_NAME => '�����ɹ�'];

    public static $error           = [API_CODE_NAME => 1000000,         API_MSG_NAME => '����ʧ��'];

    public static $accessTokenError     = [API_CODE_NAME => 1000001,   API_MSG_NAME => '����Toekn����'];
    
    public static $userTokenNull        = [API_CODE_NAME => 1000002,   API_MSG_NAME => '�û�Toekn����Ϊ��'];
    
    public static $apiUrlError          = [API_CODE_NAME => 1000003,   API_MSG_NAME => '�ӿ�·������'];
    
    public static $dataSignError        = [API_CODE_NAME => 1000004,   API_MSG_NAME => '����ǩ������'];
    
    public static $userTokenError       = [API_CODE_NAME => 1000005,   API_MSG_NAME => '�û�Toekn��������'];
    public static $userLogError       = [API_CODE_NAME => 1000006,   API_MSG_NAME => '�û���־���ɴ���'];
    public static $emptyId              = [API_CODE_NAME => 2000001,   API_MSG_NAME => 'ID����Ϊ��'];
    public static $emptyItem              = [API_CODE_NAME => 2000002,   API_MSG_NAME => '���ݲ�ѯ���������'];




    public static $userSign              = [API_CODE_NAME => 3000001,   API_MSG_NAME => '�û���ǩ��'];


}
