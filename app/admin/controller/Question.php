<?php

namespace app\admin\controller;


/**
 * 问卷调查控制器
 */
class Question extends AdminBase
{

    /**
     * 问卷调查列表
     */
    public function questionList()
    {
        $where = $this->logicQuestion->getWhere($this->param);

        $list = $this->logicQuestion->getQueList($where);

        $this->assign('list', $list);

        return  $this->fetch('question_list');
    }

    /**
     * 问卷调查添加
     */
    public function questionAdd()
    {
        $this->questionCommon();

        return $this->fetch('question_edit');
    }

    /**
     * 问卷调查编辑
     */
    public function questionEdit()
    {
        $this->questionCommon();
        $info = $this->logicQuestion->getQueInfo(['id' => $this->param['id']]);

        $this->assign('info', $info);

        return $this->fetch('question_edit');
    }

    /**
     * 问卷调查添加与编辑通用方法
     */
    public function questionCommon()
    {

        IS_POST && $this->jump($this->logicQuestion->questionEdit($this->param));
    }

    /**
     * 问卷调查删除
     */
    public function questionDel($id = 0)
    {

        $this->jump($this->logicQuestion->questionDel(['id' => $id]));
    }

    /**
     * 数据状态设置
     */
    public function setStatus()
    {

        $this->jump($this->logicAdminBase->setStatus('Questionclass', $this->param));
    }


    /**
     * 问卷调查的问题列表
     */
    public function questionItem()
    {

        $where = empty($this->param['id']) ? ['questionclassid' => 0] : ['questionclassid' => $this->param['id']];

//        $where = $this->logicQuestion->getQueWhere($this->param);

        $list = $this->logicQuestion->getQueItem($where);

        $this->assign('list', $list);

        return  $this->fetch('question_item');
    }

    /**
     * 问卷调查问题添加
     */
    public function questionItemAdd()
    {
        $this->questionItemCommon();

        return $this->fetch('question_content');
    }

    /**
     * 问卷调查问题编辑
     */
    public function questionItemEdit()
    {
        $this->questionItemCommon();
        $info = $this->logicQuestion->getQueItemInfo(['id' => $this->param['id']]);

        $this->assign('info', $info);

        return $this->fetch('question_content');
    }

    /**
     * 问卷调查问题添加与编辑通用方法
     */
    public function questionItemCommon()
    {
        $item = $this->logicQuestion->getQueList(['q.status'=>1],'q.id,name');
        $this->assign('item',$item);
        IS_POST && $this->jump($this->logicQuestion->questionItemEdit($this->param));
    }

    /**
     * 问卷调查问题删除
     */
    public function questionItemDel($id = 0)
    {

        $this->jump($this->logicQuestion->questionItemDel(['id' => $id]));
    }

    /**
     * 数据状态设置
     */
    public function setItemStatus()
    {

        $this->jump($this->logicAdminBase->setStatus('Question', $this->param));
    }

}