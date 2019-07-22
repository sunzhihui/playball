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
 * 行为日志控制器
 */
class Log extends AdminBase
{
    
    /**
     * 日志列表
     */
    public function logList()
    {
        
        $this->assign('list', $this->logicLog->getLogList());
        
        return $this->fetch('log_list');
    }
  
    /**
     * 日志删除
     */
    public function logDel($id = 0)
    {
        
        $this->jump($this->logicLog->logDel(['id' => $id]));
    }
  
    /**
     * 日志清空
     */
    public function logClean()
    {
        
        $this->jump($this->logicLog->logDel([DATA_STATUS_NAME => DATA_NORMAL]));
    }


    /**
     * 用户日志列表
     */
    public function userlogList()
    {
        $this->assign('list', $this->logicUserLog->getLogList());

        return $this->fetch('userlog_list');
    }
    /**
     * 用户日志删除
     */
    public function userlogDel($id = 0)
    {

        $this->jump($this->logicUserLog->userlogDel(['id' => $id]));
    }
    /**
     * 用户日志清空
     */
    public function userlogClean()
    {

        $this->jump($this->logicUserLog->userlogDel([DATA_STATUS_NAME => DATA_NORMAL]));
    }
}
