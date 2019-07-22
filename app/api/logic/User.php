<?php

namespace app\api\logic;

use app\api\error\Common as CommonError;
use app\api\error\CodeBase;
use app\api\logic\Common as CommonApi;
use app\common\logic\File as CommonFile;
use app\common\model\Score;
use think\Db;

class User extends ApiBase
{

    public static $commonFileLogic = null;

    /**
     * �����ʼ��
     */
    public function __construct()
    {
        // ִ�и��๹�췽��
        parent::__construct();

        if(empty(static::$commonFileLogic)){
            static::$commonFileLogic = get_sington_object('File', CommonFile::class);
        }

    }
    /**
     * ���ֻ���
     */
    public function setPhone($data = [])
    {
        $userInfo = get_member_by_token($data['user_token']);

        if($userInfo->phone) return CommonError::$phoneBindError;
        $validate_result = $this->validateUser->scene('setPhone')->check($data);

        if (!$validate_result) return CommonError::$phoneCodeEmpty;
        if (!CommonApi::is_mobile_phone($data['phone'])) return CommonError::$phoneError;

        begin:
        //����code_id��ѯ��֤��

        $codeInfo = $this->modelCode->getInfo(['id' => $data['code_id']]);

        if ($data['code'] !== /*$codeInfo['code']*/'123456') {
            return CommonError::$codewordError;
        }

        $user = $this->modelUser->getInfo(['phone' => $data['phone']]);

        if ($user) return CommonError::$phoneExist;

        $list = [
            'userid' => $userInfo->user_id,
            'phone' => $data['phone'],
            'name' => substr_replace($data['phone'], '****', 3, 4),
        ];

        $result = $this->logicUser->setInfo($list);
        $result && user_log('���ֻ���', '�û�' . $userInfo->user_id . '���ֻ���'.$data['phone'],$userInfo->user_id );
        return $result ? $data['phone'] : CommonError::$setPhoneFail;
    }

    /**
     * �ҵ�Ǯ��
     */
    public function wallet($data = [])
    {
        $type = $data['type'] ? $data['type'] : 1;
        $status = $data['type'] == 1 ? 2 : 0;

        $userInfo = get_member_by_token($data['user_token']);
        //������ϸչʾ3�ռ�¼ ֧����ϸչʾ6���¼�¼
        $time =$data['type'] == 1 ? strtotime(date("Y-m-d", strtotime("-3 days"))) : strtotime(date("Y-m-d", strtotime("-6 months")));

        $map = [
            'create_time'=> ['>=', $time],
            'userid' => ['=', $userInfo->user_id],
            'status' => ['<>', 0]
        ];
        if($type == 1){
            $list = Db::name('score')->where($map)->where(['type'=>1])->field(['score','remark','create_time'])->order('create_time desc')->select();
            $arr['list'] = $this->modelUser->groupVisit($list,$type,$status,$userInfo->user_id);
        }else{
            $list = Db::name('score')->where($map)->where(['type'=>['<>',1]])->field(['score','remark','create_time'])->order('create_time desc')->select();
            $arr['list'] = $this->modelUser->pay($list,$status,$userInfo->user_id);
        }
        //����������
        $post = [
            'type' => 1,
            'userid' => $userInfo->user_id,
            'status' => 1
        ];
        $arr['todayScore'] = Score::where($post)->whereTime('create_time','today')->sum('score');
        //�ۼ�������
        $arr['totalScore'] = Score::where(['type' => 1,'userid' => $userInfo->user_id,'status'=>1])->sum('score');
        //�ۼ�֧�����
        $where = [
            'type' => ['=', 2],
            'status' => ['<>' ,2],
            'userid' => $userInfo->user_id,
        ];
        $arr['outScore'] = Score::where($where)->sum('score');

        $userData = $this->modelUser->getInfo(['userid' => $userInfo->user_id],'score,money');

        $arr['useableScore'] = $userData['score'];
        //���ֱ���
        $score_bl = parse_config_array('score_bl');
        //����
        $arr['exchangeRate'] = $score_bl['0'];
        $arr['useableMoney'] = $userData['money'];
        return $arr;
    }

    /**
     * ���������Ϣ
     */
    public function inviteInfo($data = [])
    {

        $userInfo = get_member_by_token($data['user_token']);
        if($data['type'] == 1){
            //���������Ϣ
            //�������Ź���
            $list['sendValue'] = parse_config_array('yqconfig_send');
            $list['getValue'] = parse_config_array('yqconfig_get');
            $userArr = $this->modelUser->getInfo(['userid' => $userInfo->user_id],'yqcode');
            $list['yqcode'] = $userArr['yqcode'];
            //��Ч����
            $list['effectiveFriend'] = $this->modelUser->where(['pid'=>$userInfo->user_id,'status'=>1])->count();
            //�ѵ�������
            $list['revenue'] = $this->modelYqmoney->where(['userid'=>$userInfo->user_id,'status'=>1,'ifsend'=>1])->sum('score');
            //Ԥ������
            $list['expectedReturn'] = $this->modelYqmoney->where(['userid'=>$userInfo->user_id,'status'=>0,'ifsend'=>0])->sum('score');

        }else{
            //��������
            $this->modelYqmoney->alias('y');
            $join = [
                [SYS_DB_PREFIX . 'user u', 'y.cid = u.userid', 'LEFT'],
            ];
            $this->modelYqmoney->join = $join;
            $list['data'] = $this->modelYqmoney->getInfo(['y.userid'=>$userInfo->user_id,'y.status'=>1,'y.ifsend'=>1],'u.name,y.score,paydate,day');
        }
        return $list;

    }

    /**
     * �������
     */
    public function invite($data = [])
    {
        $userInfo = get_member_by_token($data['user_token']);
        $userList = $this->modelUser->getInfo(['userid' => $userInfo->user_id],'pid');
        if($userList['pid']){
            return CommonError::$pidError;
        }
        //��������Ϣ
        $inviteUser = $this->modelUser->getInfo(['yqcode' => $data['code']],'score,userid,money');
        //�������Ѿ����������
        $pcount =$this->modelUser->getInviteCount(['pid'=>$inviteUser['userid'],'status'=>1]);
        //��ѯ����
        $getValue = parse_config_array('yqconfig_get');
        //���ֱ���
        $score_bl = parse_config_array('score_bl');
        //�������Ź���
        $sendValue = parse_config_array('yqconfig_send');

        $reward = $this->modelUser->rewardPoints($getValue,$pcount);
        //���� = ��� * ����
        $scoreTotal = $reward * $score_bl['0'];
        $result = $this->modelUser->spcmoneyAdd($userInfo,$sendValue,$inviteUser,$score_bl[0],$scoreTotal);

        if($result ){
            //�жϽ��������Ƿ����ר���ͽ�һ
            $this->ifspcmoney($inviteUser['userid']);
            user_log('�������', '�û�' . $inviteUser['userid'] . '�������'.$userInfo->user_id.'����ý��score��' . $scoreTotal,$inviteUser['userid']);
            return CodeBase::$success;
        }else{
            return CodeBase::$error;
        }
    }

    /**
     * ���������б�
     */
    public function helpList()
    {
        $data['hotList'] = $this->logicHelp->getHelpList(['if_hot'=>1,'status'=>1],'id,name','create_time desc',false);
        $data['type'] = parse_config_array('help_gethelp');
        return $data;
    }

    /**
     * ����typeֵ �ж�
     * ����idֵ ��ѯ
     */
    public function helpDetail($data = [])
    {
        if($data['type'] == 1){
            //����id��ѯ����
            $type = parse_config_array('help_gethelp');
            $typeList['typeName'] = $type[$data['id']];

            $helpList = $this->logicHelp->getHelpList(['catid'=>$data['id'],'status'=>1],'id,name','create_time desc',false);
            $list = array_merge($typeList,$helpList);
        }else{
            //����id��ѯ��������

            $list = $this->logicHelp->getHelpInfo(['id'=>$data['id']],'name,content');
        }
        return $list;
    }

    /**
     * ����
     */
    public function feedback($data = [])
    {
        $userInfo = get_member_by_token($data['user_token']);

        $type = parse_config_array('help_gethelp');

        $picData = $this->loginFile->pictureUpload('img');
        $data['img_ids'] = $picData['id'];
        $result = $this->modelFeedback->setInfo([
            'catid' => $data['catid'],
            'name' => $type[$data['catid']],
            'content' => $data['content'],
            'userid' => $userInfo->user_id,
            'img_ids' =>$picData['id'],
            'contact' => $data['contact']
        ]);
        $result ? $result : CodeBase::$error;
    }

    /**
     * �ҵķ���
     */
    public function feedbackList($data = [])
    {
        $userInfo = get_member_by_token($data['user_token']);
        $this->modelFeedback->alias('f');
        $join = [
            [SYS_DB_PREFIX . 'picture p', 'f.img_ids = p.id', 'LEFT'],
        ];
        $where['f.' . DATA_STATUS_NAME] = ['neq', DATA_DELETE];

        $this->modelFeedback->join = $join;

        return $this->modelFeedback->getList(['f.userid'=>$userInfo->user_id,'f.status'=>1], 'f.name,content,contact,p.name as p_name,path', 'f.create_time desc', false);
    }

    //ǩ�����
    function signin($data = []){
        $longsign=0;//����ǩ������
        $userInfo = get_member_by_token($data['user_token']);
        $userid=$userInfo->user_id;
        //��ѯ��ǰ�û�����ǩ�����
        $info=$this->modelSignin->getInfo(['userid'=>$userid,'status'=>DATA_NORMAL],'*');
        if(!empty($info)){
           if($info['longday']==7){
               $longsign=0;
           }else{
               if(date("Y-m-d",strtotime("-1 day"))==date("Y-m-d",strtotime($info['create_time']))){
                   $longsign=$info['longday'];
               }else{
                   $longsign=0;
               }
           }

        }
        //��ѯ���û������Ƿ�ǩ��
        $insign=$this->issign($userid);//�����Ƿ�ǩ��
        $rinfo['insign']=$insign;
        $rinfo['longsign']=$longsign;
        $rinfo['score_signin']=parse_config_array('score_signin');
        return $rinfo;
    }
    //�жϵ����Ƿ�ǩ��
    function issign($userid){
        $insign=0;
        $start = strtotime(date('Y-m-d 00:00:00'));
        $end = time();
        $where['create_time'] = array('between',"$start,$end");
        $where['userid'] = ['=',$userid];
        $where['status'] = ['=',DATA_NORMAL];
        $todaySign=Db::name('signin')->where($where)->find();
        !empty($todaySign) && $insign=1;
        return $insign;
    }
    //ȥǩ��
    function gosignin($data = []){
        $score_signin=parse_config_array('score_signin');;
        $userInfo = get_member_by_token($data['user_token']);
        $userid=$userInfo->user_id;
        //��ѯ���û������Ƿ�ǩ��
        $insign=$this->issign($userid);
        if($insign==1){
            return CodeBase::$userSign;
        }

        //��ѯ��ǰ�û�����ǩ�����
        $info=$this->modelSignin->getList(['userid'=>$userid,'status'=>DATA_NORMAL],'*','id desc',0);
        if(empty($info[0])){
            //���û�ǩ����ǩ��������һ�쿪ʼ����������Ϊ0
            $score=$score_signin[1];
            $res=$this->modelSignin->setInfo(['userid'=>$userid,'status'=>DATA_NORMAL,'longday'=>1,'score'=>$score_signin[1],'create_time'=>time()]);
        }else{
            //ǰ��������ǩ��
            //�ж��Ƿ���������ǰ���ڼ�ȥһ���Ƿ�������ʱ���Ƿ���ȣ�
            if(date("Y-m-d",strtotime("-1 day"))==date("Y-m-d",strtotime($info[0]['create_time']))){
                //                //ʱ�����������ǩ��,ֱ�����ͽ��
                $longday=$info[0]['longday']+1;
                //�жϵ�ǰ���������Ƿ����7��
                if($info[0]['longday']>=7){
                    $longday=1;
                }
                $score=$score_signin[$longday];
                $res=$this->modelSignin->setInfo(['userid'=>$userid,'status'=>DATA_NORMAL,'longday'=>$longday,'score'=>$score_signin[$longday],'create_time'=>time()]);
            }else{
                //������ǩ����0��ʼ
                $score=$score_signin[1];
                $res=$this->modelSignin->setInfo(['userid'=>$userid,'status'=>DATA_NORMAL,'longday'=>1,'score'=>$score_signin[1],'create_time'=>time()]);
            }

        }
        $handle_text='ǩ��';
        user_log($handle_text, '�û�' . $handle_text . '���ͽ�ң�score��' . $score,$userid);
        $result=$this->modelScore->setInfo(['userid'=>$userid,'status'=>DATA_NORMAL,'score'=>$score,'type'=>1,'remark'=>'ǩ���ͽ��','create_time'=>time()]);
        return $res;
    }

    /**
     * �����ʾ�
     */
    public function questionList($data = [])
    {
        $userInfo = get_member_by_token($data['user_token']);

        $queClassInfo = $this->modelQuestionclass->getInfo(['if_new'=>1,'status'=>1],'id,name,remark');

        $queLog = $this->modelQuestionLog->getInfo(['userid'=>$userInfo->user_id,'questionclassid'=>$queClassInfo['id']]);
        if(!$queLog) return '���Ѳμӹ��˴��ʾ����';

        if(!$queClassInfo) return '�����ʾ����';

        $queInfo = $this->modelQuestion->getList(['questionclassid'=>$queClassInfo['id'],'status'=>1],'id,name,questiontype','sort desc');
        $list = [];
        $list['questionclass_id'] = $queClassInfo['id'];
        $list['name'] = $queClassInfo['name'];
        $list['remark'] = $queClassInfo['remark'];
        foreach($queInfo as $k=>$val){

            $data = $this->modelQuestionSel->getList(['questionid' => $val['id'],'status'=>1],'id,title,img','sort desc');
            $list['data'][] = [
                'question_id' => $val['id'],
                'q_name' => $val['name'],
                'questiontype' => $val['questiontype'],
            ];
            foreach($data as &$v){
                $img = $v['img'] ? get_picture_url($v['img']) : '';
                $list['data'][$k][] = [
                    'id' => $v['id'],
                    'title' => $v['title'],
                    'img' => $img
                ];
            }

        }
        return $list;

    }

    /**
     * �����ʾ��ύ
     */
    public function questionPost($data = [])
    {
        $userInfo = get_member_by_token($data['user_token']);

        $queClassInfo = $this->modelQuestionclass->getInfo(['id'=>$data['questionclass_id']]);
        if(!$queClassInfo) return CommonError::$questionErrorss;
        Db::startTrans();
        try{
            foreach($data['ids'] as $k=>$val){
                $this->modelQuestionResult->setInfo([
                    'questionclassid' => $data['questionclass_id'],
                    'questionid'=>$val,
                    'answer' => $data['answer'][$k],
                    'userid' =>$userInfo->user_id,
                    'create_time' => time()
                ]);
            }
            $this->modelQuestionLog->setInfo([
                'questionclassid' => $data['questionclass_id'],
                'userid' => $userInfo->user_id,
            ]);
            //�û����û��ӻ���,�����
            $userData = $this->modelUser->getInfo(['userid' => $userInfo->user_id],'score,money');
            //���ֱ���
            $score_bl = parse_config_array('score_bl');
//            $this->modelUser->where(['userid' => $userInfo->user_id])->setInc('score',$queClassInfo['score']);
            //�����û��������
            $this->modelUser->setInfo([
                'userid' => $userInfo->user_id,
                'score' => $userData['score'] + $queClassInfo['score'],
                'money' => $userData['money'] + $queClassInfo['score'] / $score_bl['0']
            ]);
            //���ֱ���
            $score_bl = parse_config_array('score_bl');
            //���ּ�¼��
            $this->modelScore->setInfo([
                'userid' => $userInfo->user_id,
                'type' => 1,
                'status' => 1,
                'remark' => '�������ʾ�����û���'.$queClassInfo['score'],
                'score' => $queClassInfo['score'],
                'money' => $queClassInfo['score'] / $score_bl['0'],
            ]);
            $this->ifspcmoney($userInfo->user_id);
            user_log('�ύ�����ʾ�', '�û�' . $userInfo->user_id . '���ͽ�ң�score��' . $queClassInfo['score'],$userInfo->user_id);
            Db::commit();
            return true;
        }catch (\Exception $e){
            Db::rollback();
//            return $e->getMessage();
            return CodeBase::$error;
        }

    }

    /**
     * ��ȡ�û���Ϣ
     */
    public function userInfo($data = [])
    {
        $userInfo = get_member_by_token($data['user_token']);
        $list = $this->modelUser->getInfo(['userid'=>$userInfo->user_id]);
        unset($list['pwd']);
        return $list;
    }

    /**
     * ��֧����/΢���˺�
     */
    public function bindNumber($data = [])
    {
        $userInfo = get_member_by_token($data['user_token']);

        $userData = $this->modelUser->getInfo(['userid' => $userInfo->user_id]);
        //��֧�����˺�
        if($data['type'] == 1){
            if($userData['zfbname'] && $userData['zfbnum']) return CommonError::$zfbError;

            $result = $this->modelUser->setInfo([
                'userid' => $userInfo->user_id,
                'zfbname' => $data['zfbname'],
                'zfbnum' => $data['zfbnum'],
            ]);
            $text = '��֧����';
            $msg = ',zfbname:'.$data['zfbname'].',zfbnum:'.$data['zfbnum'];
        }elseif($data['type'] == 2){
            //��΢���˺�
            if($userData['openid']) return CommonError::$wxError;
            $openIdInfo = $this->modelUser->getInfo(['openid'=>$data['openid']]);
            if($openIdInfo) return $this->apiReturn([API_CODE_NAME => 100002, API_MSG_NAME => '��Ȩʧ�ܣ���΢�ź�����Ȩ�����˺�'.$openIdInfo['name']],'','');

            $result = $this->modelUser->setInfo([
                'userid' => $userInfo->user_id,
                'openid' => $data['openid'],
            ]);
            $text = '��΢��';
            $msg = ',openid:'.$data['openid'];
        }
        $result && user_log($text, '�û�' . $userInfo->user_id . $text.$msg,$userInfo->user_id);
        $result ? true : CodeBase::$error;
    }

    /**
     * ʵ����֤
     */
    public function verified($data = [])
    {
        $userInfo = get_member_by_token($data['user_token']);

        $userData = $this->modelUser->getInfo(['userid' => $userInfo->user_id]);

        if($userData['ifmanager'] == 1) return CommonError::$verifiedError;
        if($userData['ifmanager'] == 3) return CommonError::$verifiedCheckError;
        $post = [
            'userid' => $userInfo->user_id,
            'card' => $data['card'],
            'ifmanager' => 3,
            'cardname' => $data['cardname'],
        ];
        if($data['type'] == 2){
            $picData1 = static::$commonFileLogic->pictureUpload('card_img1');
            $picData2 = static::$commonFileLogic->pictureUpload('card_img2');
            $arr = [
                'card_img1' => $picData1['id'],
                'card_img2' => $picData2['id']
            ];
            $post = array_merge($post,$arr);
        }

        $result = $this->modelUser->setInfo($post);

        $result && user_log('ʵ����֤', '�û�' . $userInfo->user_id . 'ʵ����֤��cardname��'.$data['cardname'].',card:'.$data['card'],$userInfo->user_id);
        $result ? true : CodeBase::$error;
    }

    //�жϽ��������Ƿ����ר���ͽ�һ
    public function ifspcmoney($userid)
    {
        //����������
        $post = [
            'type' => 1,
            'userid' => $userid,
            'status' => ['<>', 0],
        ];
        $uinfo = $this->modelUser->getInfo(['userid' => $userid], '*');
        $needscore = parse_config_array('txconfig_needscore');
        $todayScore = Score::where($post)->whereTime('create_time', 'today')->sum('score');
        empty($todayScore) && $todayScore = 0;
        //�жϵ����Ƿ񳬹�1500
        if ($todayScore >= $needscore[0]) {
            //�жϵ����Ƿ�Ϊ�ѱ������
            $nowdate = date('Y-m-d', time());
            if ($nowdate == $uinfo['getcount_date']) {
                //�����ѱ��ֱ������
                return false;
            } else {
                //û��ǣ��ж������Ƿ���
                if (date("Y-m-d", strtotime("-1 day")) == $uinfo['getcount_date']) {
                    //�����ѱ�ǣ�����������������ʱ����λ��Ϊ����
                    $getcount=($uinfo['getcount'] + 1);
                } else {
                    //����δ��ǣ�������ȡ��������ϲ㣬ʱ����λ��Ϊ���첢����������1��
                    $getcount=1;
                    //δ���ֵ�ר�����Ծ����
                    Db::name('Spcmoney')->where(['userid' => $userid,'type' => [['=',1],['=',3],'or'],'status' => 1,'cantixian'=>0])->update(['day'=>0]);

                }
                $this->modelUser->setInfo(['userid' => $userid, 'getcount' => $getcount, 'getcount_date' => date('Y-m-d', time())]);
                $this->setaddday($this->modelspcmoney->getInfo(['type' => 1, 'userid' => $userid, 'status' => 1]), $userid, 1);
                $this->setaddday($this->modelspcmoney->getInfo(['type' => 3, 'userid' => $userid, 'status' => 1]), $userid, 3);


            }

        } else {
            return false;

        }
    }

    //ר�����ֻִ����������
    public function setaddday($spcinfo, $userid, $type)
    {
        if ($type == 1) {
            $day = 5;
        } else {
            $day = 10;
        }
        if (!empty($spcinfo)) {
            //�Ƿ������
            if ($spcinfo['cantixian'] != 1) {
                $addday = $spcinfo['day'] + 1;
                $data['userid'] = $userid;
                if ($addday >= $day) {
                    $data['day'] = $day;
                    $data['cantixian'] = 1;
                } else {
                    $data['day'] = $addday;
                }
                Db::name('Spcmoney')->where(['userid' => $userid, 'type' => $type, 'status' => 1])->update($data);
            }
        }
    }

    //��ʱ����ÿ��0��ִ�У�
    public function timed()
    {
        //�������������������ȡ���+����ȡ���


        //�жϵ�ǰ����-1�Ƿ�Ϊ������ȡ��ҵ����ڣ�������������Ծ�����ϲ� getcount=0 getcount_date=''��δ���ֵ�������¼��������
    }

}
