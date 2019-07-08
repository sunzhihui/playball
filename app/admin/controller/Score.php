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
use think\Db;
/**
 * 积分控制器
 */
class Score extends AdminBase
{
    
    /**
     * 积分排行列表
     */
    public function scoreorder()
    {

        //默认时间
        $where = $this->logicScore->getWhere($this->param);
        $list=$this->logicScore->getScoreList($where);
        $this->assign('list',$list);
//        //填充搜索栏的值
        $param=$this->param;
        array_key_exists('timestart', $param)?:$param['timestart']=date("Y-m-d");
        array_key_exists('timeend', $param)?:$param['timeend']=date('Y-m-d',strtotime('+1 day'));
        $this->assign('where', $param);
        return $this->fetch('score_order');
    }

    public function scoreold()
    {

        //默认时间
        $where = $this->logicScore->getWhere($this->param);
        $list=$this->logicScore->getoldScoreList($where);
        $this->assign('list',$list);
//        //填充搜索栏的值
        $param=$this->param;
        array_key_exists('timestart', $param)?:$param['timestart']=date("Y-m-d");
        array_key_exists('timeend', $param)?:$param['timeend']=date('Y-m-d',strtotime('+1 day'));
        $this->assign('where', $param);
        return $this->fetch('score_old');
    }
    /**
     * 数据状态设置
     */
    public function setStatus()
    {
        
        $this->jump($this->logicAdminBase->setStatus('Score', $this->param));
    }
}
