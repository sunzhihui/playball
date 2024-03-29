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
class Task extends AdminBase
{

    /**
     * 任务列表
     */
    public function TaskList()
    {
        $where = $this->logicGame->getWhere($this->param);
        $where['a.gtype']=2;
        $list=$this->logicGame->getGameList($where, 'a.*,m.nickname');
        $this->assign('list', $list);
        //填充搜索栏的值
        $param=$this->param;
        array_key_exists('type', $param)?:$param['type']='';
        $this->assign('where', $param);
        return $this->fetch('task_list');
    }

    /**
     * 任务添加
     */
    public function TaskAdd()
    {

        IS_POST && $this->jump($this->logicGame->gameEdit($this->param));

        return $this->fetch('task_edit');
    }

    /**
     * 任务编辑
     */
    public function TaskEdit()
    {

        IS_POST && $this->jump($this->logicGame->gameEdit($this->param));

        $info = $this->logicGame->getGameInfo(['id' => $this->param['id']]);

        $this->assign('info', $info);

        return $this->fetch('task_edit');
    }
    /**
     * 数据状态设置
     */
    public function setStatus()
    {

        $this->jump($this->logicAdminBase->setStatus('Game', $this->param));
    }
    /**
     * 数据状态设置
     */
    public function setIftop()
    {
        $this->jump($this->logicAdminBase->setStatus('Game', $this->param,'id','iftop'));
    }
    /**
     * 排序
     */
    public function setSort()
    {
        $this->jump($this->logicAdminBase->setSort('Game', $this->param));
    }
}
