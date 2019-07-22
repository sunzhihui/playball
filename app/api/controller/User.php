<?php

namespace app\api\controller;

use app\common\model\Help;

/**
 * �û�������
 */
class User extends ApiBase
{
    /**
     * ���ֻ��ŷ���
     */
    public function setPhone()
    {

        return $this->apiReturn($this->logicUser->setPhone($this->param));
    }

    /**
     * �ҵ�Ǯ��
     */
    public function wallet()
    {

        return $this->apiReturn($this->logicUser->wallet($this->param));
    }

    /**
     * �������
     */
    public function invite()
    {
        return $this->apiReturn($this->logicUser->invite($this->param));
    }

    /**
     * ��������
     */
    public function helpList()
    {
        return $this->apiReturn($this->logicUser->helpList());
    }

    /**
     * ��������
     */
    public function helpDetail()
    {
        return $this->apiReturn($this->logicUser->helpDetail($this->param));
    }

    /**
     * ������Ϣ
     */
    public function feedback()
    {
        return $this->apiReturn($this->logicUser->feedback($this->param));
    }

    /**
     * ������Ϣ�б�
     */
    public function feedbackList()
    {
        return $this->apiReturn($this->logicUser->feedbackList($this->param));
    }

    //�û�ǩ����Ϣ
    public function signin(){

        return $this->apiReturn($this->logicUser->signin($this->param));
    }
    //�û�ȥǩ��
    public function gosignin(){
        return $this->apiReturn($this->logicUser->gosignin($this->param));
    }

    /**
     * �ʾ����
     */
    public function questionList()
    {
        return $this->apiReturn($this->logicUser->questionList($this->param));
    }

    /**
     * �ʾ�����ύ
     */
    public function questionPost()
    {
        return $this->apiReturn($this->logicUser->questionPost($this->param));
    }

    /**
     * �û���Ϣ
     */
    public function userInfo()
    {
        return $this->apiReturn($this->logicUser->userInfo($this->param));
    }

    /**
     * �˺��밲ȫ
     */
    public function bindNumber()
    {
        return $this->apiReturn($this->logicUser->bindNumber($this->param));
    }

    /**
     * ʵ����֤
     */
    public function verified()
    {
        return $this->apiReturn($this->logicUser->verified($this->param));
    }

    /**
     *  ��������б�
     */
    public function inviteInfo()
    {
        return $this->apiReturn($this->logicUser->inviteInfo($this->param));
    }

}
