<?php

namespace app\admin\validate;

/**
 * 会员验证器
 */
class User extends AdminBase
{

    // 验证规则
    protected $rule =   [
        'phone'        => 'require|max:11|/^1[34578]{1}[0-9]{9}$/|unique:user',
        'pwd'      => 'require|confirm|length:6,20',
        'name'      => 'require|min:2|unique:user',
        'score'  => 'number'
    ];

    // 验证提示
    protected $message  =   [
        'phone.require'      =>'手机号不能为空',
        'phone.unique'      => '手机号已存在',
        'pwd.require'      => '密码不能为空',
        'pwd.confirm'      => '两次密码不一致',
        'pwd.length'       => '密码长度为6-20字符',
        'name.unique'       => '昵称已存在',
        'phone./^1[34578]{1}[0-9]{9}$/'         => '手机号格式错误',
        'score'             => '积分必须为数字'
    ];

    // 应用场景
    protected $scene = [
        'add'       =>  ['phone','pwd','name','score'],
        'edit'      =>  ['name','phone','pwd.confirm','pwd.length','score'],
    ];
}
