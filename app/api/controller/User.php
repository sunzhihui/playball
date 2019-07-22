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
     * 支出明细待审核/拒绝的详细信息
     */
    public function walletInfo()
    {
        return $this->apiReturn($this->logicUser->walletInfo($this->param));
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

    //用户签到信息
    public function signin(){

        return $this->apiReturn($this->logicUser->signin($this->param));
    }
    //用户去签到
    public function gosignin(){
        return $this->apiReturn($this->logicUser->gosignin($this->param));
    }

    /**
     * 问卷调查
     */
    public function questionList()
    {
        return $this->apiReturn($this->logicUser->questionList($this->param));
    }

    /**
     * 问卷调查提交
     */
    public function questionPost()
    {
        return $this->apiReturn($this->logicUser->questionPost($this->param));
    }

    /**
     * 用户信息
     */
    public function userInfo()
    {
        return $this->apiReturn($this->logicUser->userInfo($this->param));
    }

    /**
     * 账号与安全
     */
    public function bindNumber()
    {
        return $this->apiReturn($this->logicUser->bindNumber($this->param));
    }

    /**
     * 实名认证
     */
    public function verified()
    {
        return $this->apiReturn($this->logicUser->verified($this->param));
    }

    /**
     *  邀请好友列表
     */
    public function inviteInfo()
    {
        return $this->apiReturn($this->logicUser->inviteInfo($this->param));
    }

    //个人中心
    public function percenter(){
        return $this->apiReturn($this->logicUser->percenter($this->param));
    }

    //用户提现初始化
    public function tixian_init(){
        return $this->apiReturn($this->logicUser->tixian_init($this->param));
    }

    //用户申请提现
    public function tixian_apply(){
        return $this->apiReturn($this->logicUser->tixian_apply($this->param));
    }

}
