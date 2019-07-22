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

    public static $error                = [API_CODE_NAME => 1010000, API_MSG_NAME => '����ʧ��'];

    public static $passwordError            = [API_CODE_NAME => 1010001, API_MSG_NAME => '��¼�������'];

    public static $codewordError            = [API_CODE_NAME => 1010002, API_MSG_NAME => '��֤�����'];

    public static $phoneError            = [API_CODE_NAME => 1010003, API_MSG_NAME => '�ֻ��Ÿ�ʽ����'];

    public static $phoneExist            = [API_CODE_NAME => 1010004, API_MSG_NAME => '�ֻ����Ѵ���'];

    public static $phoneBindError            = [API_CODE_NAME => 1010005, API_MSG_NAME => '�Ѱ��ֻ���'];

    public static $phoneCodeEmpty           = [API_CODE_NAME => 1010006, API_MSG_NAME => '�ֻ��Ż���֤�벻��Ϊ��'];

    public static $registerFail             = [API_CODE_NAME => 1010007, API_MSG_NAME => 'ע��ʧ��'];

    public static $phoneFail                = [API_CODE_NAME => 1010008, API_MSG_NAME => '�ֻ�����ʹ��'];

    public static $setPhoneFail                = [API_CODE_NAME => 1010009, API_MSG_NAME => '�ֻ��Ű�ʧ��'];

    public static $pidError                = [API_CODE_NAME => 1010010, API_MSG_NAME => '�Ѵ����ϼ�'];

    public static $zfbError                = [API_CODE_NAME => 1010010, API_MSG_NAME => '���Ѱ�֧����'];

    public static $wxError                = [API_CODE_NAME => 1010010, API_MSG_NAME => '���Ѱ�΢��'];

    public static $verifiedError                = [API_CODE_NAME => 1010010, API_MSG_NAME => '����ʵ����֤'];

    public static $verifiedCheckError                = [API_CODE_NAME => 1010010, API_MSG_NAME => '�����ύʵ����֤���룬�����~'];

    public static $questionError                = [API_CODE_NAME => 1010010, API_MSG_NAME => '�����ʾ����'];


}
