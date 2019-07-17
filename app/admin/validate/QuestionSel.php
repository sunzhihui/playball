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
class QuestionSel extends AdminBase
{

    // 验证规则
    protected $rule =   [
        'title'       => 'require',
    ];

    // 验证提示
    protected $message  =   [
        'title.require'         => '问卷调查标题不能为空',
    ];

    // 应用场景
    protected $scene = [
        'edit'  =>  ['title']
    ];
}
