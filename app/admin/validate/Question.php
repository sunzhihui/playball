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

namespace app\admin\validate;

/**
 * 帮助问题验证器
 */
class Question extends AdminBase
{

    // 验证规则
    protected $rule =   [
        'name'          => 'require',
        'remark'       => 'require',
        'score'   => 'require|/^([1-9][0-9]*){1,10}$/',
    ];

    // 验证提示
    protected $message  =   [
        'name.require'         => '问卷调查标题不能为空',
        'remark.require'      => '问卷调查内容不能为空',
        'score.require'  => '积分不能为空',
        'score./^([1-9][0-9]*){1,10}$/'  => '积分格式不正确',
    ];

    // 应用场景
    protected $scene = [
        'edit'  =>  ['name', 'remark', 'score']
    ];
}
