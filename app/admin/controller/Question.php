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
        $queInfo = $this->logicQuestion->getQueInfo(['id'=>$this->param['ids']]);
        if($queInfo['if_new'] == 1){
            $this->jump($this->logicQuestion->questionMsg());
        }
        $this->jump($this->logicAdminBase->setStatus('Questionclass', $this->param));
    }

    /**
     * 是否最新状态设置
     */
    public function setIfNew()
    {

        $this->jump($this->logicQuestion->setType('Questionclass','if_new',$this->param,false));
    }


    /**
     * 问卷调查的问题列表
     */
    public function questionItem()
    {

        $where = empty($this->param['id']) ? ['questionclassid' => 0] : ['questionclassid' => $this->param['id']];

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
     * 是否最新状态设置
     */
    public function questionType()
    {

        $this->jump($this->logicQuestion->setType('Question','questiontype',$this->param));
    }

    /**
     * 排序
     */
    public function setItemSort()
    {

        $this->jump($this->logicAdminBase->setSort('Question', $this->param));
    }

    /**
     * 数据状态设置
     */
    public function setItemStatus()
    {

        $this->jump($this->logicAdminBase->setStatus('Question', $this->param));
    }

    /**
     * 问卷调查的问题选项列表
     */
    public function chooseItem()
    {
        $where = empty($this->param['id']) ? ['questionid' => 0] : ['questionid' => $this->param['id']];
        $list = $this->logicQuestion->getChooseItem($where);

        $this->assign('list', $list);
        $this->assign('id',$this->param['id']);

        return  $this->fetch('choose_item');
    }

    /**
     * 问卷调查问题添加
     */
    public function chooseAdd()
    {

        $this->chooseCommon();

        return $this->fetch('choose_edit');
    }

    /**
     * 问卷调查问题编辑
     */
    public function chooseEdit()
    {
        $this->chooseCommon();
        $info = $this->logicQuestion->getChooseInfo(['id' => $this->param['id']]);

        $this->assign('info', $info);

        return $this->fetch('choose_edit');
    }

    /**
     * 问卷调查问题添加与编辑通用方法
     */
    public function chooseCommon()
    {

        $this->assign('questionid',$this->param['questionid']);
        IS_POST && $this->jump($this->logicQuestion->chooseEdit($this->param));
    }

    /**
     * 排序
     */
    public function setSort()
    {

        $this->jump($this->logicAdminBase->setSort('QuestionSel', $this->param));
    }

    /**
     * 问卷调查问题删除
     */
    public function chooseDel($id = 0)
    {

        $this->jump($this->logicQuestion->chooseDel(['id' => $id]));
    }

    /**
     * 数据状态设置
     */
    public function setChooseStatus()
    {

        $this->jump($this->logicAdminBase->setStatus('QuestionSel', $this->param));
    }



}