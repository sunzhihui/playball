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

/**
 * 文章接口控制器
 */
class User extends ApiBase
{
    
    /**
     * 用户登录并获取token
     */
    public function Login()
    {

        return $this->apiReturn($this->logicArticle->getArticleCategoryList());
    }
    
    /**
     * 文章列表接口
     */
    public function articleList()
    {
        return $this->apiReturn($this->logicArticle->getArticleList($this->param));
    }
}
