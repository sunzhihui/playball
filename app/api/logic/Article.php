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

use app\common\logic\Article as CommonArticle;
use app\common\logic\Adv as CommonAdv;

/**
 * 文章接口逻辑
 */
class Article extends ApiBase
{

    public static $commonArticleLogic = null;
    public static $commonAdvLogic = null;

    /**
     * 基类初始化
     */
    public function __construct()
    {
        // 执行父类构造方法
        parent::__construct();

        if(empty(static::$commonArticleLogic)){
            static::$commonArticleLogic = get_sington_object('Article', CommonArticle::class);
        }
        if(empty(static::$commonAdvLogic)){
            static::$commonAdvLogic = get_sington_object('Adv', CommonAdv::class);
        }

    }

    /**
     * 获取文章分类列表
     */
    public function getArticleCategoryList()
    {

        return static::$commonArticleLogic->getArticleCategoryList([], 'id,name', 'id desc', false);
    }

    /**
     * 获取文章列表
     */
    public function getArticleList($data = [])
    {

        $where = [];
        !empty($data['category_id']) && $where['a.category_id'] = $data['category_id'];
        $where['a.status']=1;
        $list=static::$commonArticleLogic->getArticleList($where, 'a.id,a.name,a.category_id,a.describe,a.create_time,a.content,a.adv_id', 'a.create_time desc');
        if($list){
            foreach ($list as &$v){
                $v['imgs']=$this->getimgs($v['content']);
                unset($v['content']);
            }
        }
        return $list;
    }
    //字符串中获取所有图片并反馈数组
    function getimgs($str) {

        if(strpos($str,'http') !== false){
            $reg1 = '/((http|https):\/\/)+(\w+\.)+(\w+)[\w\/\.\-]*(jpg|gif|png)/';
            $matches1 = array();
            preg_match_all($reg1, $str, $matches1);
            foreach ($matches1[0] as $value1) {
                $data[] = $value1;
            }
        }else{
            $reg = '/(\w+)[\w\/\.\-]*(jpg|gif|png)/';
            $matches = array();
            preg_match_all($reg, $str, $matches);
            foreach ($matches[0] as $value) {
                if($value){

                }
                $data[] = $value;
            }
        }


        return $data;
    }
    /**
     * 获取文章信息
     */
    public function getArticleInfo($data = [])
    {

        $articleInfo=static::$commonArticleLogic->getArticleInfo(['a.id' => $data['id']], 'a.*,m.nickname,c.name as category_name');

        $recommendList = $this->getArticleList(['category_id'=>$articleInfo['category_id']]);
        $advList = [];
        foreach ($recommendList as $k=>$val){
            if($val['id'] == $data['id']) unset($recommendList[$k]);
            $recommendList[$k]['time'] = wordTime(strtotime($val['create_time']));
            $adv = static::$commonAdvLogic->getAdvInfo(['id'=>$val['adv_id']],'id,name,url,create_time,cover_id');
            $adv['time'] = wordTime(strtotime($adv['create_time']));
            $advList[] = $adv;
        }
        !empty($articleInfo['content']) && $articleInfo['content']=html_entity_decode($articleInfo['content']);
        $advList = array_unique($advList);
        return ['articleInfo' => $articleInfo, 'recommendList' => $recommendList, 'advList'=> $advList];
    }
}
