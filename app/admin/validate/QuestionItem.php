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
class QuestionItem extends AdminBase
{

    // 验证规则
    protected $rule =   [
        'questionclassid'       => '/^[1-9][0-9]*$/',
        'name'          => 'require',
        'item1'       => 'require',
        'item2'       => 'require',
    ];

    // 验证提示
    protected $message  =   [
        'questionclassid./^[1-9][0-9]*$/'      => '问卷名称不能为空',
        'name.require'         => '问卷调查标题不能为空',
        'item1.require'  => '选项A不能为空',
        'item2.require'  => '选项B不能为空',
    ];

    // 应用场景
    protected $scene = [
        'edit'  =>  ['name', 'questionclassid', 'item1', 'item2']
    ];
}
