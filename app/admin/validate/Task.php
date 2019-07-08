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
 * 会员验证器
 */
class Task extends AdminBase
{
    
    // 验证规则
    protected $rule =   [
        
        'url'      => 'require',
    ];
    
    // 验证提示
    protected $message  =   [
        
        'url.require'      => '跳转ID或url不能为空',
    ];

    // 应用场景
    protected $scene = [

        'edit'      =>  ['url'],
    ];
}
