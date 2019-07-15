<?php

namespace app\admin\controller;


/**
 * 帮助中心控制器
 */
class Help extends AdminBase
{

    /**
     * 帮助列表
     */
    public function helpList()
    {
        $where = $this->logicHelp->getWhere($this->param);

        $this->assign('list', $this->logicHelp->getHelpList($where, 'h.*', 'create_time desc'));

        $helpType = parse_config_array('help_gethelp');
        $this->assign('helpType',$helpType);

        return $this->fetch('help_list');
    }

    /**
     * 帮助问题添加
     */
    public function helpAdd()
    {
        $this->helpCommon();

        return $this->fetch('help_edit');
    }

    /**
     * 帮助问题编辑
     */
    public function helpEdit()
    {
        $this->helpCommon();

        $info = $this->logicHelp->getHelpInfo(['id' => $this->param['id']]);

        $this->assign('info', $info);

        return $this->fetch('help_edit');
    }

    /**
     * 帮助问题添加与编辑通用方法
     */
    public function helpCommon()
    {
        IS_POST && $this->jump($this->logicHelp->helpEdit($this->param));
        $helpType = parse_config_array('help_gethelp');
        $this->assign('helpType',$helpType);
    }

    /**
     * 文章分类删除
     */
    public function helpDel($id = 0)
    {

        $this->jump($this->logicHelp->helpDel(['id' => $id]));
    }

    /**
     * 数据状态设置
     */
    public function setStatus()
    {

        $this->jump($this->logicAdminBase->setStatus('Help', $this->param));
    }
}