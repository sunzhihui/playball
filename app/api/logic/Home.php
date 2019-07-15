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

namespace app\api\logic;
use think\Db;
/**
 * 接口文档逻辑
 */
class Home extends ApiBase
{

    /**
     * 获取接口列表
     */
    public function getAdvList($where = [], $field = 'a.*,m.path as img', $order = '', $paginate = false)
    {

        $this->modelAdv->alias('a');

        $join = [
            [SYS_DB_PREFIX . 'picture m', 'm.id = a.cover_id'],
        ];

        $where['a.status'] = ['=', DATA_NORMAL];

        $this->modelAdv->join = $join;
        return $this->modelAdv->getList(['ifindex'=>1], $field, 'sort', $paginate);
    }
    public function getScore($where = [], $field = 'a.*,m.path', $order = ''){
        $res=$this->modelUser->getInfo($where, $field, 'sort');
        if($res['score']){
            return $res['score'];
        }else{
            return 0;
        }
    }
    public function getTaskList($where=[],$field ='a.*,m.nickname,t.path as listpic,s.path as detailpic',$order='',$paginate){
        $where['a.status'] = ['=', DATA_NORMAL];
        return $this->logicGame->getGameList($where,$field,$order,$paginate,$paginate);
    }

    //已完成任务id
    public function getEndtaskids($where){
        $where.= " and a.status=".DATA_NORMAL;
        $sql="select GROUP_CONCAT(gameid) as ids from yb_taskend a left join yb_game m on a.gameid = m.id where 1 $where";
        $ids=Db::name('taskend')->query("$sql");
        if($ids[0]['ids']!="" && $ids[0]['ids']!="null"){
            $res=explode(',',$ids[0]['ids']);
        }else{
            $res=[];
        }
        return $res;
    }
}
