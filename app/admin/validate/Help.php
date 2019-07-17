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
class Help extends AdminBase
{

    // 验证规则
    protected $rule =   [
        'name'          => 'require',
        'content'       => 'require',
        'catid'   => 'require',
    ];

    // 验证提示
    protected $message  =   [
        'name.require'         => '帮助问题标题不能为空',
        'content.require'      => '帮助问题内容不能为空',
        'category_id.require'  => '帮助问题分类必须选择',
    ];

    // 应用场景
    protected $scene = [
        'edit'  =>  ['name', 'content', 'category_id']
    ];
}
