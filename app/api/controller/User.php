<?php

namespace app\api\controller;

/**
 * 用户控制器
 */
class User extends ApiBase
{
    /**
     * 绑定手机号方法
     */
    public function setPhone()
    {

        return $this->apiReturn($this->logicUser->setPhone($this->param));
    }

    /**
     * 我的钱包
     */
    public function wallet()
    {

        return $this->apiReturn($this->logicUser->wallet($this->param));
    }

    /**
     * 邀请好友
     */
    public function invite()
    {
        return $this->apiReturn($this->logicUser->invite($this->param));
    }
}
