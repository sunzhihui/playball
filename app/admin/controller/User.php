<?php


namespace app\admin\controller;


/**
 * 用户控制器
 */
class User extends AdminBase
{

    /**
     * 用户列表
     */
    public function userList()
    {

        $where = $this->logicUser->getWhere($this->param);

        $list = $this->logicUser->getUserList($where);

        $this->assign('list', $list);

        return  $this->fetch('user_list');
    }

    /**
     * 添加用户
     */

    public function userAdd()
    {

        IS_POST && $this->jump($this->logicUser->UserAdd($this->param));

        return  $this->fetch('user_add');
    }

    /**
     * 编辑用户
     */

    public function userEdit()
    {

        IS_POST && $this->jump($this->logicUser->userEdit($this->param));


        $info = $this->logicUser->getUserInfo(['userid' => $this->param['userid']]);


        $this->assign('info', $info);

        return  $this->fetch('user_edit');
    }

    /**
     * 用户删除
     */
    public function userDel($userid = 0)
    {

        return $this->jump($this->logicUser->userDel(['userid' => $userid]));
    }

    /**
     * 用户导出
     */
    public function exportUserList()
    {

        $where = $this->logicUser->getWhere($this->param);

        $this->logicUser->exportUserList($where);
    }

    /**
     * 用户状态设置
     */
    public function setStatus()
    {
        $this->jump($this->logicAdminBase->setStatus('User', $this->param,'userid'));
    }

}
