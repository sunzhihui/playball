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
class Gamedetail extends AdminBase
{
    
    /**
     * 任务列表
     */
    public function gameDetailList()
    {
        $where = $this->logicGame->getWhere($this->param);
        $list=$this->logicGame->getGamedetailList($where, 'a.*');
        $this->assign('list', $list);
        //填充搜索栏的值
        $param=$this->param;
        array_key_exists('gameid', $param)?:$param['gameid']='';
        $this->assign('where', $param);

        return $this->fetch('gamedetail_list');
    }

    /**
     * 任务添加
     */
    public function gameDetailAdd()
    {

        IS_POST && $this->jump($this->logicGame->getGamedetailInfo($this->param));
        !empty($this->param['gameid']) && $this->assign('gameid', $this->param['gameid']);
        return $this->fetch('gamedetail_edit');
    }

    /**
     * 任务编辑
     */
    public function gameDetailEdit()
    {

        IS_POST && $this->jump($this->logicGame->gameDetailEdit($this->param));

        $info = $this->logicGame->getGamedetailInfo(['id' => $this->param['id']]);

        $this->assign('info', $info);
        $this->assign('gameid', $info['gameid']);
        return $this->fetch('gamedetail_edit');
    }
    /**
     * 数据状态设置
     */
    public function setStatus()
    {
        $parm=$this->param;
        if(!empty($parm['status']) && $parm['status']=DATA_DELETE){
            !empty($parm['ifdown']) && $parm['ifdown']==1 ? $this->jump([RESULT_ERROR,'该任务为下载任务，无法删除，请在任务头中编辑为非下载任务']):$this->jump($this->logicAdminBase->setStatus('Gamedetail', $this->param));
        }
        $this->jump($this->logicAdminBase->setStatus('Gamedetail', $this->param));
    }
    /**
     * 数据状态设置
     */
    public function setIftop()
    {
        $this->jump($this->logicAdminBase->setStatus('Gamedetail', $this->param,'id','iftop'));
    }
    /**
     * 排序
     */
    public function setSort()
    {
        $this->jump($this->logicAdminBase->setSort('Gamedetail', $this->param));
    }
}
