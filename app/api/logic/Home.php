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
use app\common\Model\Picture as CommonPicture;
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
        $where['a.status'] = DATA_NORMAL;

        return $this->logicGame->getGameList($where,$field,$order,$paginate,$paginate);
    }
    public function endids($userid){
        $where=" and a.userid=".$userid;//." and m.type=2 and a.gtype=2"
        return $this->getEndtaskids($where);
    }
    //已完成任务id
    public function getEndtaskids($where,$ifnowday=1){
        $start = date('Y-m-d 00:00:00');
        $end = date('Y-m-d H:i:s');
        $where.= " and a.status=".DATA_NORMAL;
        $sql="select GROUP_CONCAT(gamedetailid) as ids from yb_taskend a left join yb_gamedetail m on a.gamedetailid = m.id where 1 $where";//用户完成的所有子任务id
        if($ifnowday==1){
            $ids1=$ids2=Db::name('taskend')->query("$sql and a.create_time >= unix_timestamp( '$start' ) AND a.create_time <= unix_timestamp( '$end' )");//当日已完成子任务
            $ids=$ids1[0];

        }else{
            $ids1=Db::name('taskend')->query("$sql and m.ifonce = 0");//不可重复任务不受时间限制
            $ids2=Db::name('taskend')->query("$sql and m.ifonce = 1 and a.create_time >= unix_timestamp( '$start' ) AND a.create_time <= unix_timestamp( '$end' )");//当日已完成可重复任务
            $ids=array_merge($ids1[0],$ids2[0]);
        }
        if(count($ids['ids'])>0){
            $res=explode(',',$ids['ids']);
        }else{
            $res=[];
        }
        return $res;//返回子任务ID
    }

    //任务详情含任务完成情况
    function getTaskdetail($parm=[]){
        $userInfo = get_member_by_token($parm['user_token']);
        $where['userid'] = $userInfo->user_id;
        dy($where['userid']);
    }



    //通过ID获取图片路径
    function getimgUrl($cover_id){
         $img=DB::name('picture')->where("id=$cover_id")->value('path');
         return $img;
    }
}
