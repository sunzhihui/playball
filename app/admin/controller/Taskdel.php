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
    public function taskList()
    {
        $where = $this->logicTask->getWhere($this->param);
        $this->assign('list', $this->logicTask->getTaskList($where, 'a.*,m.nickname'));
        //填充搜索栏的值
        $param=$this->param;
        array_key_exists('type', $param)?:$param['type']='';
        array_key_exists('tasktype', $param)?:$param['tasktype']='';
        $this->assign('where', $param);
        $flag1=$this->logicTask->gettasktype(1);
        $flag2=$this->logicTask->gettasktype(2);
        $this->assign('flag1', $flag1);
        $this->assign('flag2', $flag2);
        return $this->fetch('task_list');
    }

    /**
     * 任务添加
     */
    public function taskAdd()
    {

        IS_POST && $this->jump($this->logicTask->taskEdit($this->param));
        $flag1=$this->logicTask->gettasktype(1);
        $flag2=$this->logicTask->gettasktype(2);
        $this->assign('flag1', $flag1);
        $this->assign('flag2', $flag2);
        return $this->fetch('task_edit');
    }

    /**
     * 任务编辑
     */
    public function taskEdit()
    {

        IS_POST && $this->jump($this->logicTask->taskEdit($this->param));

        $info = $this->logicTask->getTaskInfo(['id' => $this->param['id']]);
        $flag1=$this->logicTask->gettasktype(1);
        $flag2=$this->logicTask->gettasktype(2);
        $this->assign('flag1', $flag1);
        $this->assign('flag2', $flag2);
        $this->assign('info', $info);

        return $this->fetch('task_edit');
    }
//
//    /**
//     * 任务添加与编辑通用方法
//     */
//    public function taskCommon()
//    {
//
//        IS_POST && $this->jump($this->logicTask->taskEdit($this->param));
//
//        //$this->assign('article_category_list', $this->logicTask->getArticleCategoryList([], 'id,name', '', false));
//        $this->assign();
//    }
    /**
     * 数据状态设置
     */
    public function setStatus()
    {
        
        $this->jump($this->logicAdminBase->setStatus('Task', $this->param));
    }
}
