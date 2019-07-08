<?php

namespace app\admin\controller;


/**
 * 兑换记录管理控制器
 */
class Tixian extends AdminBase
{

    /**
     * 兑换记录列表
     */
    public function tixianList()
    {

        $where = $this->logicMembermoney->getWhere($this->param);

        $where['s.status'] = ['=',0];
        $where['s.type'] = ['=',2];

        $list = $this->logicMembermoney->getScoreList($where);

        $this->assign('list', $list);
        return $this->fetch('tixian_list');
    }

    /**
     * 兑换记录记录审核
     */

    public function  scoreExamine()
    {

        $this->jump($this->logicMembermoney->examine($this->param));
    }


    /**
     * 兑换记录列表查看
     */

    public function passList()
    {

        $where = $this->logicMembermoney->getPassWhere($this->param);

        if(empty($where['s.status'])) $where['s.status'] = ['<>',0];

        $where['s.type'] = ['=',2];


        $list = $this->logicMembermoney->getScoreList($where);

        $this->assign('list', $list);
        return $this->fetch('pass_list');
    }

}
