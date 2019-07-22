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

namespace app\admin\logic;
/**
 * 行为日志逻辑
 */
class userLog extends AdminBase
{
    
    /**
     * 获取日志列表
     */
    public function getLogList()
    {
        
        //$sub_member_ids = $this->logicMember->getSubMemberIds(MEMBER_ID);
        
        $where = [];
        
        //$sub_member_ids[] = MEMBER_ID;
        
        //!IS_ROOT && $where['userid'] = ['in', $sub_member_ids];
        return $this->modeluserLog->getList($where, true, 'create_time desc');
    }
  
    /**
     * 日志删除
     */
    public function userLogDel($where = [])
    {
        
        return $this->modelUserLog->deleteInfo($where) ? [RESULT_SUCCESS, '日志删除成功'] : [RESULT_ERROR, $this->modelUserLog->getError()];
    }
    
    /**
     * 日志添加
     */
    public function userLogAdd($name = '', $describe = '',$userid)
    {
        $uinfo = $this->modelUser->getInfo(['userid'=>$userid],'userid,name,phone');

        $request = request();
        
        $data['userid'] = $uinfo['userid'];
        $data['username']  = $uinfo['phone'];
        $data['ip']        = $request->ip();
        $data['url']       = $request->url();
        $data['status']    = DATA_NORMAL;
        $data['name']      = $name;
        $data['describe']  = $describe;
        return $this->modelUserLog->setInfo($data) ? [RESULT_SUCCESS, '日志添加成功']: [RESULT_ERROR, $this->modelUserLog->getError()];
    }
}
