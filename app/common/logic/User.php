<?php

namespace app\common\logic;

/**
 *  用户逻辑
 */
class User extends LogicBase
{

    /**
     * 获取用户信息
     */

    public function getUserList($where = [], $field = 'u.*', $order = '', $paginate = DB_LIST_ROWS)
    {
        $this->modelUser -> alias('u');
        $list = $this->modelUser->getList($where,$field,$order,$paginate);

        return $list;

    }

    /**
     * 获取用户列表搜索条件
     */

    public function getWhere($data = [])
    {
        $where = [];
        !empty($data['search_data']) && $where['name|phone'] = ['like', '%'.$data['search_data'].'%'];
        return $where;
    }

    /**
     * 会员添加
     */

    public function userAdd($data = [])
    {

        $validate_result = $this->validateUser->scene('add')->check($data);

        if (!$validate_result) {

            return [RESULT_ERROR, $this->validateUser->getError()];
        }

        $url = url('userList');

        $data = [
            'phone' => $data['phone'],
            'pwd' => data_md5_key($data['pwd']),
            'photo' => $data['photo'],
            'name' => $data['name'],
            'birdate' => $data['birdate'],
            'sex' => $data['sex'],
            'card' => $data['card'],
            'ifmanager' => $data['ifmanager'],
            'status' => $data['status'],
            'score' => $data['score']
        ];

        $result = $this->modelUser->setInfo($data);

        $result && action_log('新增', '新增用户，name：' . $data['name']);

        return $result ? [RESULT_SUCCESS, '用户添加成功', $url] : [RESULT_ERROR, $this->modelUser->getError()];
    }


    /**
     * 获取用户信息
     */

    public function getUserInfo($where = [], $field = true)
    {
        $info = $this->modelUser->getInfo($where, $field);

        return $info;
    }

    /**
     * 会员编辑
     */

    public function userEdit($data = [])
    {

        $validate_result = $this->validateUser->scene('edit')->check($data);

        if (!$validate_result) {

            return [RESULT_ERROR, $this->validateUser->getError()];
        }
        $url = url('userList');

        $list = [
            'userid' => $data['userid'],
            'phone' => $data['phone'],
            'name' => $data['name'],
            'birdate' => $data['birdate'],
            'sex' => $data['sex'],
            'card' => $data['card'],
            'ifmanager' => $data['ifmanager'],
            'status' => $data['status'],
            'score' => $data['score']
        ];

        if($data['pwd']) $list['pwd'] = data_md5_key($data['pwd']);
        if($data['photo']) $list['photo'] = $data['photo'];

        $result = $this->modelUser->setInfo($list);

        $result && action_log('编辑', '编辑用户，userid：' . $data['userid']);

        return $result ? [RESULT_SUCCESS, '用户编辑成功', $url] : [RESULT_ERROR, $this->modelUser->getError()];
    }


    /**
     * 用户删除
     */

    public function UserDel($where = [])
    {

        $url = url('userList');

        $result = $this->modelUser->deleteInfo($where);

        $result && action_log('删除', '删除用户，where：' . http_build_query($where));

        return $result ? [RESULT_SUCCESS, '用户删除成功', $url] : [RESULT_ERROR, $this->modelMember->getError(), $url];
    }

    /**
     * 导出用户列表
     */
    public function exportUserList($where = [], $field = 'u.*', $order = '')
    {

        $list = $this->getUserList($where, $field, $order, false);

        $titles = "手机号,昵称,常住地,生日,身份证,积分,注册时间";
        $keys   = "phone,name,default_address,birdate,card,score,create_time";

        action_log('导出', '导出用户列表');

        export_excel($titles, $keys, $list, '用户列表');
    }

}