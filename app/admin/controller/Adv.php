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

namespace app\admin\controller;

/**
 * 任务控制器
 */
class Adv extends AdminBase
{
    
    /**
     * 任务列表
     */
    public function advList()
    {
        $where = $this->logicAdv->getWhere($this->param);
        $this->assign('list', $this->logicAdv->getAdvList($where, 'a.*,m.nickname'));
        //填充搜索栏的值
        $param=$this->param;
        array_key_exists('ifindex', $param)?:$param['ifindex']='';
//        array_key_exists('advtype', $param)?:$param['advtype']='';
        $this->assign('where', $param);
        return $this->fetch('adv_list');
    }

    /**
     * 任务添加
     */
    public function advAdd()
    {

        IS_POST && $this->jump($this->logicAdv->advEdit($this->param));

        return $this->fetch('adv_edit');
    }

    /**
     * 任务编辑
     */
    public function advEdit()
    {

        IS_POST && $this->jump($this->logicAdv->advEdit($this->param));

        $info = $this->logicAdv->getAdvInfo(['id' => $this->param['id']]);

        $this->assign('info', $info);

        return $this->fetch('adv_edit');
    }
    /**
     * 数据状态设置
     */
    public function setStatus()
    {
        
        $this->jump($this->logicAdminBase->setStatus('Adv', $this->param));
    }
    /**
     * 排序
     */
    public function setSort()
    {
        $this->jump($this->logicAdminBase->setSort('Adv', $this->param));
    }
}
