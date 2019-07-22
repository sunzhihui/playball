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

namespace app\api\controller;
use app\api\error\CodeBase;

/**
 * 文章接口控制器
 */
class Article extends ApiBase
{

    /**
     * 新闻分类接口
     */
    public function categoryList()
    {
        return $this->apiReturn($this->logicArticle->getArticleCategoryList());
    }

    /**
     * 新闻列表接口
     */
    public function articleList()
    {
        $res['list']=$this->logicArticle->getArticleList($this->param);
        $res['category']=$this->logicArticle->getArticleCategoryList();
        return $this->apiReturn($res);
    }
    //新闻详情
    public function ArticleInfo(){

        empty($this->param['id']) && $this->logicApiBase->apiError(CodeBase::$emptyId);

        return $this->apiReturn($this->logicArticle->getArticleInfo($this->param));
    }
}
