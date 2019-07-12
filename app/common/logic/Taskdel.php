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
class Taskdel extends LogicBase
{

    /**
     * 获取任务列表
     */
    public function getTaskList($where = [], $field = 'a.*,m.nickname', $order = '')
    {

        $this->modelTask->alias('a');

        $join = [
            [SYS_DB_PREFIX . 'member m', 'a.member_id = m.id'],
            [SYS_DB_PREFIX . 'game p', 'a.url = c.id'],
        ];

        $where['a.' . DATA_STATUS_NAME] = ['neq', DATA_DELETE];

        $this->modelTask->join = $join;
        $list = $this->modelTask->getList($where, $field, $order);
        //dy(json_encode($list));
        //如果类型是小游戏或文章，视频类应该返回封面

        foreach ($list as &$v) {
            //暂时只处理小游戏和APP下载类 视频不处理
            if ($v['tasktype'] == 4 || $v['tasktype'] == 2) {
                //文章类
                $this->modelTask->alias('a');
                $join = [
                    [SYS_DB_PREFIX . 'game c', 'a.url = c.id'],
                    [SYS_DB_PREFIX . 'picture m', 'm.id = c.cover_id'],
                ];
                $where['a.' . DATA_STATUS_NAME] = ['neq', DATA_DELETE];
                $where['a.id'] = ['=', $v['id']];
                //dy($where);
                $this->modelTask->join = $join;
                $info = $this->modelTask->getInfo($where, 'a.*,c.name,c.describe,c.cover_id,m.path as listpic');
                if ($info) {
                    $v['cover_id'] = $info['cover_id'];
                    $v['title'] = $info['name'];
                    $v['describe'] = $info['describe'];
                    $v['score'] = $info['score'];
                    $v['listpic'] = $info['listpic'];
                }
            }
            $flag1=$this->gettasktype(1);
            $flag2=$this->gettasktype(2);
            //1新人 2日常 3任务中心 4星球福利社 5进阶任务
            switch ($v['type']) {
                case 1:
                    $v['taskitme'] = $flag1[1];
                    break;
                case 2:
                    $v['taskitme'] = $flag1[2];
                    break;
                case 3:
                    $v['taskitme'] = $flag1[3];
                    break;
                case 4:
                    $v['taskitme'] = $flag1[4];
                    break;
                case 5:
                    $v['taskitme'] = $flag1[5];
                    break;

                default;
                    break;
            }
            //1阅读新闻 2下载app 3观看视频 4玩小游戏 5邀请好友
            switch ($v['tasktype']) {
                case 1:
                    $v['taskcontent'] = $flag2[1];
                    break;
                case 2:
                    $v['taskcontent'] = $flag2[2];
                    break;
                case 3:
                    $v['taskcontent'] = $flag2[3];
                    break;
                case 4:
                    $v['taskcontent'] = $flag2[4];
                    break;
                case 5:
                    $v['taskcontent'] = $flag2[5];
                    break;
                default;
                    break;
            }
        }
        return $list;
    }
    /*
     * */
    public function gettasktype($flag='1'){
        $array=array();
        if($flag==1){
            $array=[
                '1'=>'新手任务',
                '2'=>'今日任务',
                '3'=>'高额任务',
                '4'=>'进阶任务',
                '5'=>'星球福利社',
                ];
        }else{
            $array=[
                '1'=>'阅读任务',
                '2'=>'下载app',
                '3'=>'观看视频',
                '4'=>'玩小游戏',
                '5'=>'其他任务',
            ];
        }
        return $array;
    }

    /**
     * 获取文章列表搜索条件
     */
    public function getWhere($data = [])
    {

        $where = [];
        !empty($data['search_data']) && $where['a.title|a.describe'] = ['like', '%' . $data['search_data'] . '%'];
        !empty($data['type']) && $where['a.type'] = ['=', $data['type']];
        !empty($data['tasktype']) && $where['a.tasktype'] = ['=', $data['tasktype']];
        return $where;
    }

    /**
     * 任务信息编辑
     */
    public function taskEdit($data = [])
    {
        //验证器需要验证唯一性，如果URL为ID类则需要保持唯一性
        if ($data['tasktype'] != 1 || $data['tasktype'] != 5) {
            //查询当前url对应的项目id
            //暂时只处理下载类和游戏类
            $item = $this->modelGame->find(['id' => $data['url']]);
            if (empty($item)) {
                return [RESULT_ERROR, 'url输入框关联的ID在任务类型下不存在'];
            }
            $info = $this->getTaskInfo(['url' => $data['url']], 'url');
            if (empty($data['id'])) {
                if (count($info) >= 1) {
                    return [RESULT_ERROR, 'url输入框关联的ID已存在相关任务'];
                }
            } else {
                if (count($info) > 1) {
                    return [RESULT_ERROR, 'url输入框关联的ID已存在相关任务'];
                }
            }
        }
//        $validate_result = $this->validateTask->scene('edit')->check($data);
//
//        if (!$validate_result) {
//
//            return [RESULT_ERROR, $this->validateArticle->getError()];
//        }
        $url = url('taskList');

        empty($data['id']) && $data['member_id'] = MEMBER_ID;

        //$data['content'] = html_entity_decode($data['content']);//html处理
        $result = $this->modelTask->setInfo($data);

        $handle_text = empty($data['id']) ? '新增' : '编辑';

        $result && action_log($handle_text, '任务' . $handle_text . '，title：' . $data['title']);

        return $result ? [RESULT_SUCCESS, '任务操作成功', $url] : [RESULT_ERROR, $this->modelTask->getError()];
    }

    /**
     * 获取任务信息
     */
    public function getTaskInfo($where = [], $field = true)
    {

        $info = $this->modelTask->getInfo($where, $field);

        //$info['leader_nickname'] = $this->modelMember->getValue(['id' => $info['memberid']], 'nickname');
        //dy($info);
        return $info;
    }
}
