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
        $where['s.type'] = ['<>',1];

        $list = $this->logicMembermoney->getScoreList($where);

        $this->assign('list', $list);
        return $this->fetch('tixian_list');
    }

    /**
     * 兑换记录记录审核
     */

    public function  scoreExamine()
    {

        IS_POST && $this->jump($this->logicMembermoney->examine($this->param));

        $this->assign('scoreid',$this->param['scoreid']);
        return $this->fetch('score_examine');
    }


    /**
     * 兑换记录列表查看
     */

    public function passList()
    {

        $where = $this->logicMembermoney->getPassWhere($this->param);

        $where['s.status'] = ['<>',0];

        $where['s.type'] = ['<>',1];


        $list = $this->logicMembermoney->getScoreList($where);

        $this->assign('list', $list);
        return $this->fetch('pass_list');
    }

}
