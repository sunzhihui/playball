<?php

namespace app\api\controller;

use app\common\model\Help;

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

    /**
     * 帮助问题
     */
    public function helpList()
    {
        return $this->apiReturn($this->logicUser->helpList());
    }

    /**
     * 问题详情
     */
    public function helpDetail()
    {
        return $this->apiReturn($this->logicUser->helpDetail($this->param));
    }

    /**
     * 反馈信息
     */
    public function feedback()
    {
        return $this->apiReturn($this->logicUser->feedback($this->param));
    }

    /**
     * 反馈信息列表
     */
    public function feedbackList()
    {
        return $this->apiReturn($this->logicUser->feedbackList($this->param));
    }

}
