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

namespace app\common\logic;

/**
 * 回收站逻辑
 */
class Game extends LogicBase
{

    /**
     * 获取游戏列表
     */
    public function getGameList($where = [], $field = 'a.*,m.nickname,t.path as logopic,s.path as listpic', $order = 'a.sort',$paginate=0)
    {
        //如果传入userid 需要过滤掉
        if(!empty($where['userid'])){
            $wherestr=['userid'=>$where['userid'],'status'=>1];
            unset($where['userid']);
        }
        $this->modelGame->alias('a');
        $join = [
            [SYS_DB_PREFIX . 'member m', 'a.member_id = m.id'],
            [SYS_DB_PREFIX . 'picture t', 't.id = a.cover_id','left'],
            [SYS_DB_PREFIX . 'picture s', 's.id = a.dphoto','left'],
        ];
        $where['a.' . DATA_STATUS_NAME] = ['neq', DATA_DELETE];

        $this->modelGame->join = $join;
        $list=$this->modelGame->getList($where, $field, $order,$paginate);
        if(!empty($wherestr)){
            foreach($list as &$v){
                if(!empty($v['id'])){
                    $ids=$this->logicHome->getEndtaskids(' and userid = '.$wherestr['userid'].' and m.gameid='.$v['id'],0);
                    $v['havefinish']=$ids;
                }
            }
        }

        return $list;
    }
    /**
     * 获取文章列表搜索条件
     */
    public function getWhere($data = [])
    {

        $where = [];
        !empty($data['search_data']) && $where['a.name|a.describe'] = ['like', '%'.$data['search_data'].'%'];
        !empty($data['gameid']) && $where['a.gameid'] = ['=', $data['gameid']];
        return $where;
    }

    /**
     * 游戏信息编辑
     */
    public function gameEdit($data = [])
    {
        //验证器
//        $validate_result = $this->validateGame->scene('edit')->check($data);
//
//        if (!$validate_result) {
//
//            return [RESULT_ERROR, $this->validateArticle->getError()];
//        }
        if($data['gtype']==1){
            $url = url('gameList');
        }else{
            $url = url('taskList');
        }
        empty($data['id']) && $data['member_id'] = MEMBER_ID;

        $data['content'] = html_entity_decode($data['content']);//html处理
        $result = $this->modelGame->setInfo($data);

        empty($data['id'])?$id=$result:$id=$data['id'];
        $Gamedetail = $this->modelGamedetail->getInfo(['gameid'=>$id,'ifdown'=>1]);//查询是否有瞎下载任务
        if($data['needdown']==1){
            //新增需要判断是否为下载任务，选择1则需要新增一条下载子任务
            if($Gamedetail){
                $this->modelGamedetail->setInfo([DATA_STATUS_NAME=>DATA_NORMAL],['gameid'=>$id,'ifdown'=>1]);
            }else{
                //新增
                $this->modelGamedetail->setInfo($arr=['name'=>'下载'.$data['name'],'ifdown'=>1,'remark'=>'下载送金币啦！','gameid'=>$id,'btn'=>'去下载']);
            }
        }else{
            //不需要（删除签到任务）
            $this->modelGamedetail->setInfo([DATA_STATUS_NAME=>DATA_DELETE],['gameid'=>$id,'ifdown'=>1]);
        }

        $handle_text = empty($data['id']) ? '新增' : '编辑';
        //写日志
        $result && action_log($handle_text, '游戏' . $handle_text . '，name：' . $data['name']);

        return $result ? [RESULT_SUCCESS, '游戏操作成功', $url] : [RESULT_ERROR, $this->modelGame->getError()];
    }
    /**
     * 获取游戏信息
     */
    public function getGameInfo($where = [], $field = true)
    {

        $info = $this->modelGame->getInfo($where, $field);
        return $info;
    }

    //获取子任务详情列表
    function getGamedetailList($where = [], $field = 'a.*', $order = 'a.sort',$paginate=0){
        if(!empty($where['g.userid'])){

            $wherestr=['userid'=>$where['g.userid'],'status'=>1];
            unset($where['g.userid']);
        }
        $this->modelGamedetail->alias('a');
        $join = [
//            [SYS_DB_PREFIX . 'game m', 'a.gameid = m.id'],
        ];
        $where['a.' . DATA_STATUS_NAME] = ['neq', DATA_DELETE];
        $this->modelGamedetail->join = $join;
        $list=$this->modelGamedetail->getList($where, $field, $order,$paginate);
        return $list;
    }

    //获取子任务详情
    function getGamedetailInfo($where = [], $field = true){
        $info = $this->modelGamedetail->getInfo($where, $field);
        return $info;
    }
    //编辑子任务详情
    function gameDetailEdit($data = []){

        $url = url('gameDetailList',['gameid'=>$data['gameid']]);

        empty($data['id']) && $data['member_id'] = MEMBER_ID;

        $result = $this->modelGamedetail->setInfo($data);

        $handle_text = empty($data['id']) ? '新增' : '编辑';
        //写日志
        $result && action_log($handle_text, '子任务' . $handle_text . '，name：' . $data['name']);

        return $result ? [RESULT_SUCCESS, '子任务操作成功', $url] : [RESULT_ERROR, $this->modelGame->getError()];
    }


}
