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
use app\common\Model\Gamedetail as CommonGamedetail;
use app\api\error\CodeBase;
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
        $userid = $userInfo->user_id;
        $this->modelGame->alias('a');

        $join = [
            [SYS_DB_PREFIX . 'picture t', 't.id = a.cover_id','left'],
            [SYS_DB_PREFIX . 'picture s', 's.id = a.dphoto','left'],
        ];

        $where['a.status'] =  DATA_NORMAL;
        $where['a.id'] =  $parm['gameid'];

        $this->modelGame->join = $join;

        $info=$this->modelGame->getInfo($where,'a.*,t.path as logopic,s.path as listpic');
        empty($info) && $this->apiError(CodeBase::$emptyItem);
        $ids=$this->logicHome->getEndtaskids(' and userid = '.$userid.' and m.gameid='.$info['id'],0);
        $info['havefinish']=$ids;
        //子任务列表
        $info['childs']=get_sington_object('Gamedetail', CommonGamedetail::class)->getList(['gameid'=>$info['id']],'*','sort asc',false);
        !empty($info['img_ids']) && $info['img_ids']=$this->getimgUrl($info['img_ids']);
        !empty($info['gimg_ids']) && $info['gimg_ids']=$this->getimgUrl($info['gimg_ids']);

        //查询历史收益
        $info['oldtotal']=$this->getoldscore($info['id']);

        $this->apiError($info);
    }
    //查询历史收益
    function getoldscore($pid){
        $oldtotal=$this->modelScore->getInfo(['pid'=>$pid,'type'=>1],'ifnull(sum(score),0) scores');
        $res['oldscoreList']=$this->modelScore->getList(['pid'=>$pid,'type'=>1],'remark,create_time,score','',false);
        $res['oldscore']=$oldtotal['scores'];
        return $res;
    }

    //通过ID获取图片路径
    function getimgUrl($cover_ids){
        $imgids=explode(',',$cover_ids);
        $img_url=[];
        foreach($imgids as $v){
            $img=DB::name('picture')->field('path')->where("id = $v")->value('path');
            $img_url[]=$img;
        }
         return $img_url;
    }

    //去签到
    function gosign(){

    }

    //签到情况
    function signList(){

    }
}
