<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
//use QL\QueryList;
use think\Db;
use QL\QueryList;
class Caiji
{
    function __construct(){
        parent::__construct();
        echo 2;die;
        var_dump($this->yt_album());die;
    }

    
   
public function yt_album()
    {
        return 1;die;
        set_time_limit(0);
        ini_set('memory_limit', '30768M');
        $album_str = input('album_str');
        $album_id_arr = explode(',', $album_str);
        foreach ($album_id_arr as $k => $v) {
            $album_info = $this->get_fullFlag($v);
            //$fullFlag=$album_info['fullFlag'];
            // $wordCnt=$album_info['wordCnt'];

            $get_cate_arr = $this->get_cate_id($album_info['clsName']);
            $p_cate_id = $get_cate_arr['id'];
            $column_id = $get_cate_arr['column_id'];
            $album_re = Db::name('album')->field('id')->where(array('t_id' => $v))->find();
            if ($album_re) {
                $album_id = $album_re['id'];
            } else {
                if ($album_info['photoPath']) {
                    $img_save_path = $this->downloadImage($album_info['photoPath']);
                } else {
                    $img_save_path = '/public/album_img/nocover.jpg';
                }
                if ($album_info['fullFlag'] == 2) {
                    $state_id = 1;
                } else {
                    $state_id = 2;
                }
                $album_arr = array(
                   
                    'album_name' => $album_info['name'],
                    'column_id' => $column_id,
                    'p_cate_id' => $p_cate_id,
                    'checks_id' => 1,
                    'img' => $img_save_path,
                    'content' => $album_info['intro'],
                    'update_time' => strtotime($album_info['lastUpdate']),
                    'state_id' => $state_id,
                    'quality_id' => 2,
                    'album_info' => $album_info['intro'],
                    'letter' => $this->getFirstCharter((string)$album_info['name']),
                    'words_num' => $album_info['wordCnt'],
                    'cai_type' => 2,
                    't_id' => $album_info['id'],
                    'author' => $album_info['author'],
                );
                $album_id = Db::name('album')->insertGetId($album_arr);
                
            }
           
            $t_id = $v;
          
            $chapter_url = "http://app.youzibank.com/book/chapter/list?bookId=$t_id&userId=&pageNo=1&pageSize=1000";
            $chapter_list = $this->get_content($chapter_url);
            if ($chapter_list->enumCode == 'SUCCESS') {
                if ($chapter_list->data) {
                    $chapter_count = $chapter_list->pageCount;
                    $all_chapter_list = array();
                    for ($j = 1; $j <= $chapter_count; $j++) {
                        $new_chapter_url = "http://app.youzibank.com/book/chapter/list?bookId=$t_id&userId=&pageNo=$j&pageSize=1000";
                        $new_chapter_list = $this->get_content($new_chapter_url);
                        $chapter_array = $new_chapter_list->data;
                        foreach ($chapter_array as $key => $val) {
                            $all_chapter_list[($j - 1) * 1000 + $key]['chapter_name'] = $val->name;
                            $all_chapter_list[($j - 1) * 1000 + $key]['wordCnt'] = $val->wordCnt;
                            $all_chapter_list[($j - 1) * 1000 + $key]['filePath'] = $val->filePath;

                        }
                    }
                    $last_chapter_re = Db::name('plug_files')->field('title')->where(array('album_id' => $album_id))->order('id desc')->find();//exit();
                    $splice_key = '';
                    foreach ($all_chapter_list as $key => $val) {
                        if ($val['chapter_name'] == $last_chapter_re['title']) {
                            $splice_key = $key;
                            continue;
                        }
                    }
                    if (strlen($splice_key) > 0) {
                        $all_chapter_list = array_splice($all_chapter_list, $splice_key + 1, count($all_chapter_list));
                    }
                    foreach ($all_chapter_list as $key => $val) {
                        $splice_key = (int)$splice_key;
                        if ($splice_key > 0) {
                            $num = $splice_key + $key + 2;
                        } else {
                            if ($last_chapter_re) {
                                $num = $splice_key + $key + 2;
                            } else {
                                $num = $splice_key + $key + 1;
                            }
                        }

                        $path1 = ROOT_PATH . '/public/album_txt/' . $album_id . '/' . $num . '.txt';


                        
                        if (file_exists($path1)) {
                            $txt_path['path'] = '/public/album_txt/' . $album_id . '/' . $num . '.txt';
                            $txt_path['file_size'] = $this->Tokb(filesize($path1));;
                        } else {
                            if ($val['filePath']) {
                                $chapter_content = @file_get_contents('https://book.chengxinqinye.com/book' . $val['filePath']);
                                if (!$chapter_content) {
                                    $chapter_content = '暂无内容';
                                }
                                $txt_path = $this->add_txt($album_id, $chapter_content, $num);
                            } else {
                                $txt_path['path'] = '';
                                $txt_path['file_size'] = '';
                            }
                        }

                        $chapter_arr = array(
                            'uptime' => date('Y-m-d H:i:s', time()),
                            'title' => $val['chapter_name'],
                            'album_id' => $album_id,
                            'yuebi' => 0,
                            'uptime1' => date('Y-m-d', time()),
                            'uptime2' => date('Y-m-d', time()),
                            'path' => $txt_path['path'],
                            'words_num' => $val['wordCnt'],
                            'chapter_type' => 0,
                            'checks_id' => 1,
                            'file_size' => $txt_path['file_size'],
                        );
                        Db::name('plug_files')->insert($chapter_arr);

                    }
                  
                }
            }
           
        }
    }
    //有兔更新章节
    public function yt_chapter()
    {

        set_time_limit(0);
        ini_set('memory_limit', '30768M');//exit();
        $p = input('page') ? input('page') : 1;
        $pagesize = 1000;
        $start = ($p - 1) * $pagesize;
        $start_time = strtotime(date('Y-m-d', time()) . '00:00:00');
        $start_time1 = strtotime(date('Y-m-d', time()) . '00:00:00') - 86400;
       
        $album_list = Db::name('album')->field('id,t_id,state_id')->where(array('cai_type' => 2, 'state_id' => 1))->where('update_time<' . $start_time1)->limit($start, $pagesize)->select();
        if (!$album_list) {
            $album_list = Db::name('album')->field('id,t_id,state_id')->where(array('cai_type' => 2, 'state_id' => 1))->where('update_time<' . $start_time)->limit($start, $pagesize)->select();
        }
        foreach ($album_list as $k => $v) {
            $album_id = $v['id'];
            $t_id = $v['id'];
          
            $chapter_url = "http://app.youzibank.com/book/chapter/list?bookId=$t_id&userId=&pageNo=$j&pageSize=1000";
            $chapter_list = $this->get_content($chapter_url);
            if ($chapter_list->enumCode == 'SUCCESS') {
                if ($chapter_list->data) {
                   
                    $all_chapter_list = array();
                    for ($j = 1; $j <= $chapter_count; $j++) {
                        $new_chapter_url = "http://app.youzibank.com/book/chapter/list?bookId=$t_id&userId=&pageNo=$j&pageSize=1000";
                        $new_chapter_list = $this->get_content($new_chapter_url);
                        $chapter_array = $new_chapter_list->data;
                        foreach ($chapter_array as $key => $val) {
                            $all_chapter_list[($j - 1) * 1000 + $key]['chapter_name'] = $val->name;
                            $all_chapter_list[($j - 1) * 1000 + $key]['wordCnt'] = $val->wordCnt;
                            $all_chapter_list[($j - 1) * 1000 + $key]['filePath'] = $val->filePath;

                        }
                    }
					
                    $last_chapter_re = Db::name('plug_files')->field('title')->where(array('album_id' => $album_id))->order('id desc')->find();//exit();
                    $splice_key = '';
                    foreach ($all_chapter_list as $key => $val) {
                        if ($val['chapter_name'] == $last_chapter_re['title']) {
                            $splice_key = $key;
                            continue;
                        }
                    }
                    if (strlen($splice_key) > 0) {
                        $all_chapter_list = array_splice($all_chapter_list, $splice_key + 1, count($all_chapter_list));
                    }
                    foreach ($all_chapter_list as $key => $val) {
                        $splice_key = (int)$splice_key;
                        if ($splice_key > 0) {
                            $num = $splice_key + $key + 2;
                        } else {
                            if ($last_chapter_re) {
                                $num = $splice_key + $key + 2;
                            } else {
                                $num = $splice_key + $key + 1;
                            }
                        }

                        $path1 = ROOT_PATH . '/public/album_txt/' . $album_id . '/' . $num . '.txt';


                      
                        if (file_exists($path1)) {
                            $txt_path['path'] = '/public/album_txt/' . $album_id . '/' . $num . '.txt';
                            $txt_path['file_size'] = $this->Tokb(filesize($path1));;
                        } else {
                            if ($val['filePath']) {
                                $chapter_content = @file_get_contents('https://book.chengxinqinye.com/book' . $val['filePath']);
                                if (!$chapter_content) {
                                    $chapter_content = '暂无内容';
                                }
                                $txt_path = $this->add_txt($album_id, $chapter_content, $num);
                            } else {
                                $txt_path['path'] = '';
                                $txt_path['file_size'] = '';
                            }
                        }

                        $chapter_arr = array(
                            'uptime' => date('Y-m-d H:i:s', time()),
                            'title' => $val['chapter_name'],
                            'album_id' => $album_id,
                            'yuebi' => 0,
                            'uptime1' => date('Y-m-d', time()),
                            'uptime2' => date('Y-m-d', time()),
                            'path' => $txt_path['path'],
                            'words_num' => $val['wordCnt'],
                            'chapter_type' => 0,
                            'checks_id' => 1,
                            'file_size' => $txt_path['file_size'],
                        );
                        Db::name('plug_files')->insert($chapter_arr);

                    }
                  
                }
            }
            $album_info = $this->get_fullFlag($album_id);
            if ($album_info['fullFlag'] == 2) {
                $state_id = 1;
            } else {
                $state_id = 2;
            }
            $album_arr = array(
                'update_time' => time(),
                'state_id' => $state_id,
                'words_num' => $album_info['wordCnt'],
            );
            Db::name('album')->where('id', $album_id)->update($album_arr);

        }
    }
	//凤凰小说
  public function fh_album(){
        set_time_limit(0);
        ini_set('memory_limit', '30768M');
        
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../model/QueryList.php';
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../model/phpQuery.php';
       
        $t1 = microtime(true);
        //载入视图页面
        //采集某页面所有的图片
        //书本详情
        $album_str = input('album_str')?input('album_str'):173659;
        $album_id_arr = explode(',', $album_str);

        foreach ($album_id_arr as $k => $v) {
            $start_id=floor($v/1000);
            $album_info = QueryList::Query('https://m.fhxiaoshuo.com/wapread/'.$start_id.'/' . $v . '/', array(
                'album_name' => ['.block_txt2 h2', 'text'],
                'author' => ['.block_txt2 p:eq(1) a', 'text'],
                'state_name' => ['.block_txt2 p:eq(3)', 'text'],
                'intro' => ['.intro_info', 'text'],
                'path' => ['.block_img2 img', 'src'],
                'cate_name' => ['.block_txt2 p:eq(2) a', 'text'],
                'lastUpdate' => ['.block_txt2 p:eq(4) ', 'text'],
            ))->data;

            if ($album_info) {
                $album_info = $album_info[0];
                if ($album_info['state_name']) {
                    $state_arr = explode('：', $album_info['state_name']);
                    $album_info['state_name'] = $state_arr[1];
                }
                if ($album_info['lastUpdate']) {
                    $lastUpdate_arr = explode('：', $album_info['lastUpdate']);
                    $album_info['lastUpdate'] = $lastUpdate_arr[1];
                }
                if ($album_info['state_name'] == '连载中') {
                    $album_info['state_id'] = 1;
                } else {
                    $album_info['state_id'] = 2;
                }
                switch ($album_info['cate_name']) {
                    case '玄幻':
                        $p_cate_id = 6;
                        break;
                    case '仙侠':
                        $p_cate_id = 8;
                        break;
                    case '架空':
                        $p_cate_id = 10;
                        break;
                    case '古言':
                        $p_cate_id = 13;
                        break;
                    case '历史':
                        $p_cate_id = 13;
                        break;
                    case '审美':
                        $p_cate_id = 10;
                        break;
                    case '都市':
                        $p_cate_id = 10;
                        break;
                    case '网游小说':
                        $p_cate_id = 14;
                        break;
                    case '穿越':
                        $p_cate_id = 16;
                        break;
                    case '恐怖灵异':
                        $p_cate_id = 17;
                        break;
                    case '其他':
                        $p_cate_id = 18;
                        break;
                    default:
                        $p_cate_id = 18;
                        break;
                }
               $album_re = Db::name('album')->field('id')->where(array('author' => $album_info['author'], 'album_name' => $album_info['album_name']))->find();
                if ($album_re) {
                    Db::name('album')->where(array('id' => $album_re['id']))->update(['state_id' => $album_info['state_id'], 'f_id' => $v, 'update_time' => time()]);
                    $album_id = $album_re['id'];
                } else {
                    if ($album_info['path'] && $album_info['path'] != 'https://m.fhxiaoshuo.com/static/base/style/noimg.jpg?v=15') {
                        $img_save_path = $this->downloadImage('http://www.abcxs.com' . $album_info['path']);
                    } else {
                        $img_save_path = '/public/album_img/nocover.jpg';
                    }
                    $album_arr = array(
                        'album_name' => $album_info['album_name'],
                        'column_id' => 1,
                        'p_cate_id' => $p_cate_id,
                        'checks_id' => 1,
                        'img' => $img_save_path,
                        'content' => $album_info['intro'],
                        'update_time' => strtotime($album_info['lastUpdate']),
                        'state_id' => $album_info['state_id'],
                        'quality_id' => 2,
                        'album_info' => $album_info['intro'],
                        'letter' => $this->getFirstCharter((string)$album_info['album_name']),
                        'words_num' => 0,
                        'cai_type' => 5,
                        'f_id' => $v,
                        'author' => $album_info['author'],
                    );
                    $album_id = Db::name('album')->insertGetId($album_arr);
                }
                //获取章节列表
                $str = $this->getHtml('https://m.fhxiaoshuo.com/wapbook/'.$start_id .'/'. $v . '/');
                $pagenum = QueryList::Query($str, array(
                    'page' => ['.page', 'text']
                ))->data;
                $page_arr=explode('页',$pagenum[1]['page']);
                $page_num_arr=explode('/',$page_arr[1]);
                $chapter_count=$page_num_arr[1];
               
                $chapter_info = array();
                for ($j = 1; $j <= $chapter_count; $j++) {
                    $new_chapter_url = "https://m.fhxiaoshuo.com/wapbook/".$start_id."/".$v."_".$j."/";
                    $chapter_info_arr = QueryList::Query($new_chapter_url, array(
                        'chapter_name' => ['.chapter li a', 'text'],
                        'chapter_url' => ['.chapter li a', 'href'],
                    ))->data;
                    foreach ($chapter_info_arr as $key => $val) {
                        $chapter_info[($j - 1) * 20 + $key]['chapter_name'] = $val['chapter_name'];
                        $chapter_info[($j - 1) * 20 + $key]['chapter_url'] = $val['chapter_url'];

                    }
                }
               
                $sum_words_num = 0;
                $last_chapter_re = Db::name('plug_files')->field('title')->where(array('album_id' => $album_id))->order('id desc')->find();//exit();
                $splice_key = '';
                foreach ($chapter_info as $key => $val) {
                    if ($val['chapter_name'] == $last_chapter_re['title']) {
                        $splice_key = $key;
                        continue;
                    }
                }
                if (strlen($splice_key) > 0) {
                    $chapter_info = array_splice($chapter_info, $splice_key + 1, count($chapter_info));
                }

                foreach ($chapter_info as $key => $val) {

                    $splice_key = (int)$splice_key;
                    if ($splice_key > 0) {
                        $num = $album_id . '_' . ($splice_key + $key + 2);
                    } else {
                        $num = $album_id . '_' . ($splice_key + $key + 1);
                    }
                   
                    $content_info = QueryList::Query($val['chapter_url'], array(
                        'content' => ['#nr1', 'text'],
                    ))->data;

                    if ($content_info) {
                        $content =$content_info[0]['content'];

                       
                        $file_path = ROOT_PATH . '/public/album_txt/' . $album_id;
                        if (!file_exists($file_path)) {
                            mkdir($file_path);
                        }
                       
                        $path = ROOT_PATH . '/public/album_txt/' . $album_id . '/' . $num . '.txt';
                        $path1 = '/public/album_txt/' . $album_id . '/' . $num . '.txt';
                        $myfile = fopen($path, "w") or die("Unable to open file!");
                        fwrite($myfile, $content);
                        fclose($myfile);
                        $chapter_arr = array(
                            'uptime' => date('Y-m-d H:i:s', time()),
                            'title' => $val['chapter_name'],
                            'album_id' => $album_id,
                            'yuebi' => 0,
                            'uptime1' => date('Y-m-d', time()),
                            'uptime2' => date('Y-m-d', time()),
                            'path' => $path1,
                            'words_num' => $this->utf8_strlen($content),
                            'chapter_type' => 0,
                            'checks_id' => 1,
                            'file_size' => $this->Tokb(filesize(ROOT_PATH . $path1)),
                        );
                        Db::name('plug_files')->insert($chapter_arr);
                        $sum_words_num += $this->utf8_strlen($content);
                    }

                }
                Db::name('album')->where('id', $album_id)->setInc('words_num', $sum_words_num);
            }
        }
    }
	public function fh_chapter()
    {
        set_time_limit(0);
        ini_set('memory_limit', '30768M');

        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../model/QueryList.php';
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../model/phpQuery.php';
      
        $t1 = microtime(true);

        //载入视图页面
        //采集某页面所有的图片
        //书本详情
       
        $start_time = strtotime(date('Y-m-d', time()) . '00:00:00');
        $start_time1 = strtotime(date('Y-m-d', time()) . '00:00:00') - 86400;
        $p = input('page') ? input('page') : 1;
        $pagesize = 1000;
        $start = ($p - 1) * $pagesize;
        $album_list = Db::name('album')->field('id,f_id,state_id')->where(array('cai_type' => 5, 'state_id' => 1))->where('f_id>0 and update_time<' . $start_time1)->limit($start, $pagesize)->select();
        if (!$album_list) {
            $album_list = Db::name('album')->field('id,f_id,state_id')->where(array('cai_type' => 5, 'state_id' => 1))->where('f_id>0 and update_time<' . $start_time)->limit($start, $pagesize)->select();
        }
        foreach ($album_list as $k => $v) {
            $start_id=floor($v['f_id']/1000);
            $album_info = QueryList::Query('https://m.fhxiaoshuo.com/wapread/'.$start_id.'/' . $v['f_id'] . '/', array(
                'album_name' => ['.block_txt2 h2', 'text'],
                'author' => ['.block_txt2 p:eq(1) a', 'text'],
                'state_name' => ['.block_txt2 p:eq(3)', 'text'],
            ))->data;


            if ($album_info) {
				$album_info=$album_info[0];
                if ($album_info['state_name']) {
                    $state_arr = explode('：', $album_info['state_name']);
                    $album_info['state_name'] = $state_arr[1];
                }
                if ($album_info['state_name'] == '连载中') {
                    $album_info['state_id'] = 1;
                } else {
                    $album_info['state_id'] = 2;
                }
                Db::name('album')->where(array('id' => $v['id']))->update(['state_id' => $album_info['state_id'], 'update_time' => time()]);
                //获取章节列表
                $str = $this->getHtml('https://m.fhxiaoshuo.com/wapbook/'.$start_id .'/'. $v['f_id'] . '/');
                $pagenum = QueryList::Query($str, array(
                    'page' => ['.page', 'text']
                ))->data;
                $page_arr=explode('页',$pagenum[1]['page']);
                $page_num_arr=explode('/',$page_arr[1]);
                $chapter_count=$page_num_arr[1];
                $chapter_info = array();
                for ($j = 1; $j <= $chapter_count; $j++) {
                    $new_chapter_url = "https://m.fhxiaoshuo.com/wapbook/".$start_id."/".$v['f_id']."_".$j."/";
                    $chapter_info_arr = QueryList::Query($new_chapter_url, array(
                        'chapter_name' => ['.chapter li a', 'text'],
                        'chapter_url' => ['.chapter li a', 'href'],
                    ))->data;
                    foreach ($chapter_info_arr as $key => $val) {
                        $chapter_info[($j - 1) * 20 + $key]['chapter_name'] = $val['chapter_name'];
                        $chapter_info[($j - 1) * 20 + $key]['chapter_url'] = $val['chapter_url'];

                    }
                }
                $last_chapter_re = Db::name('plug_files')->field('title')->where(array('album_id' => $v['id']))->order('id desc')->find();//exit();
                $splice_key = '';
                foreach ($chapter_info as $key => $val) {
                    if ($val['chapter_name'] == $last_chapter_re['title']) {
                        $splice_key = $key;
                        continue;
                    }
                }
                if (strlen($splice_key) > 0) {
                    $chapter_info = array_splice($chapter_info, $splice_key + 1, count($chapter_info));
                }
              
				$sum_words_num=0;
                foreach ($chapter_info as $key => $val) {
                    $content_info = QueryList::Query($val['chapter_url'], array(
                        'content' => ['#nr1', 'text'],
                    ))->data;
                    if ($content_info) {
                        $splice_key = (int)$splice_key;
                        if ($splice_key > 0) {
                            $num = $splice_key + $key + 2;
                        } else {
                            if ($last_chapter_re) {
                                $num = $splice_key + $key + 2;
                            } else {
                                $num = $splice_key + $key + 1;
                            }
                        }


                        $file_path = ROOT_PATH . '/public/album_txt/'. $v['id'];
                        if (!file_exists($file_path)) {
                            mkdir($file_path);
                        }
                       
                        $path = ROOT_PATH . '/public/album_txt/' . $v['id'] . '/' . $v['id'].'_'.$num . '.txt';
                        $path1 = '/public/album_txt/'. $v['id'] . '/' . $v['id'].'_'.$num . '.txt';
                        $myfile = fopen($path, "w") or die("Unable to open file!");
                        fwrite($myfile, $content_info[0]['content']);
                        fclose($myfile);
                        $chapter_arr = array(
                            'uptime' => date('Y-m-d H:i:s', time()),
                            'title' => $val['chapter_name'],
                            'album_id' => $v['id'],
                            'yuebi' => 0,
                            'uptime1' => date('Y-m-d', time()),
                            'uptime2' => date('Y-m-d', time()),
                            'path' => $path1,
                            'words_num' => $this->utf8_strlen($content_info[0]['content']),
                            'checks_id' => 1,
                            'chapter_type' => 0,
                            'file_size' => $this->Tokb(filesize(ROOT_PATH . $path1)),
                        );
                        Db::name('plug_files')->insert($chapter_arr);
						 $sum_words_num += $this->utf8_strlen($content_info[0]['content']);
                    }

                }
				 Db::name('album')->where('id', $v['id'])->update(['update_time' => time()]);
                Db::name('album')->where('id', $v['id'])->setInc('words_num', $sum_words_num);
            }
			
        }
    }
    //有兔 没有章节txt自动下载
    public function zd_chapter()
    {
        set_time_limit(0);
        ini_set('memory_limit', '30768M');
        $album_id = input('album_id');
        echo 1;
        exit();
        if ($album_id) {
            $chapter_url = "http://app.youzibank.com/book/chapter/list?bookId=$album_id&userId=&pageNo=1&pageSize=1000";
            $chapter_list = $this->get_content($chapter_url);
            if ($chapter_list->enumCode == 'SUCCESS') {
                if ($chapter_list->data) {
                    $chapter_count = $chapter_list->pageCount;
                    for ($j = 1; $j <= $chapter_count; $j++) {
                        $new_chapter_url = "http://app.youzibank.com/book/chapter/list?bookId=$album_id&userId=&pageNo=$j&pageSize=1000";
                        $new_chapter_list = $this->get_content($new_chapter_url);
                        $chapter_array = $new_chapter_list->data;
                        foreach ($chapter_array as $key => $val) {
                            $path1 = '/public/album_txt/' . $album_id . '/' . $val->orderNo . '.txt';
                            if (!file_exists($path1)) {
                                if ($val->filePath) {
                                    $chapter_content = file_get_contents('http://book.chengxinqinye.com/book' . $val->filePath);
                                    $txt_path = $this->add_txt($album_id, $chapter_content, $val->orderNo);
                                } else {
                                    $txt_path['path'] = '';
                                    $txt_path['file_size'] = '';
                                }
                                $chapter_arr = array(
                                    'uptime' => date('Y-m-d H:i:s', time()),
                                    'title' => $val->name,
                                    'album_id' => $album_id,
                                    'yuebi' => 0,
                                    'uptime1' => date('Y-m-d', time()),
                                    'uptime2' => date('Y-m-d', time()),
                                    'path' => $txt_path['path'],
                                    'words_num' => $val->wordCnt,
                                    'chapter_type' => 0,
                                    'checks_id' => 1,
                                    'file_size' => $txt_path['file_size'],
                                );
                                Db::name('plug_files')->insert($chapter_arr);
                            }
                        }
                    }
                }
            }
            $album_info = $this->get_fullFlag($album_id);
            if ($album_info['fullFlag'] == 2) {
                $state_id = 1;
            } else {
                $state_id = 2;
            }
            $album_arr = array(
                'update_time' => strtotime($album_info['lastUpdate']),
                'state_id' => $state_id,
                'words_num' => $album_info['wordCnt'],
            );
            Db::name('album')->where('id', $album_id)->update($album_arr);
        }
    }

    //求书网更新书
    public function qs_album()
    {
        set_time_limit(0);
        ini_set('memory_limit', '30768M');
      
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../model/QueryList.php';
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../model/phpQuery.php';
      
        $t1 = microtime(true);

        //载入视图页面
        //采集某页面所有的图片
        //书本详情
       
        $album_str = input('album_str');
        $album_id_arr = explode(',', $album_str);
        foreach ($album_id_arr as $k => $v) {
            $album_info = QueryList::Query('https://www.qiushuzw.com/txt' . $v . '/', array(
                'album_name' => ['.tit1 h1', 'text'],
                'author' => ['.date span:eq(0)', 'text'],
                'path' => ['.book_img img', 'src'],
                'intro' => ['#con_ph1_1', 'text'],
                'cate_name' => ['#con_ph1_2 td:eq(0)', 'text'],
                'words_num' => ['#con_ph1_2 td:eq(1)', 'text'],
                'state_name' => ['#con_ph1_2 td:eq(2)', 'text'],
                'lastUpdate' => ['.date span:eq(2)', 'text'],
            ))->data;


            if ($album_info) {
                $album_info = $album_info[0];
                $album_info['author'] = str_replace('小说作者：', '', $album_info['author']);
                $album_info['words_num'] = str_replace('完成字数：', '', $album_info['words_num']);
                $album_info['state_name'] = str_replace('写作进程：', '', $album_info['state_name']);
                $album_info['cate_name'] = str_replace('作品大类：', '', $album_info['cate_name']);
                $album_info['lastUpdate'] = str_replace('更新日期：', '', $album_info['lastUpdate']);
                if ($album_info['state_name'] == '连载中') {
                    $album_info['state_id'] = 1;
                } else {
                    $album_info['state_id'] = 2;
                }

                switch ($album_info['cate_name']) {
                    case '东方玄幻':
                        $p_cate_id = 6;
                        break;
                    case '国术武侠':
                        $p_cate_id = 6;
                        break;
                    case '异术超能':
                        $p_cate_id = 6;
                        break;
                    case '奇幻修真':
                        $p_cate_id = 8;
                        break;
                    case '西方奇幻':
                        $p_cate_id = 8;
                        break;
                    case '魔法幻情':
                        $p_cate_id = 8;
                        break;
                    case '都市言情':
                        $p_cate_id = 10;
                        break;
                    case '浪漫言情':
                        $p_cate_id = 10;
                        break;
                    case '耽美言情':
                        $p_cate_id = 10;
                        break;
                    case '都市生活':
                        $p_cate_id = 10;
                        break;
                    case '青春校园':
                        $p_cate_id = 10;
                        break;
                    case '官场职场':
                        $p_cate_id = 10;
                        break;
                    case '谍战特工':
                        $p_cate_id = 10;
                        break;
                    case '军事战争':
                        $p_cate_id = 13;
                        break;
                    case '历史军事':
                        $p_cate_id = 13;
                        break;
                    case '历史传奇':
                        $p_cate_id = 13;
                        break;
                    case '宫廷贵族':
                        $p_cate_id = 13;
                        break;
                    case '神话王朝':
                        $p_cate_id = 13;
                        break;
                    case '侦探推理':
                        $p_cate_id = 11;
                        break;
                    case '网游竞技':
                        $p_cate_id = 14;
                        break;
                    case '体育竞技':
                        $p_cate_id = 14;
                        break;
                    case '网络游戏':
                        $p_cate_id = 14;
                        break;
                    case '科幻未来':
                        $p_cate_id = 16;
                        break;
                    case '穿越重生':
                        $p_cate_id = 16;
                        break;
                    case '恐怖惊悚':
                        $p_cate_id = 17;
                        break;
                    case '其他小说':
                        $p_cate_id = 18;
                        break;
                    default:
                        $p_cate_id = 18;
                        break;
                }

                $album_re = Db::name('album')->field('id,cai_type,album_name')->where(array('author' => $album_info['author'], 'album_name' => $album_info['album_name']))->find();
               
                if ($album_re) {
					
                    Db::name('album')->where(array('id' => $album_re['id']))->update(['words_num' => $album_info['words_num'], 'state_id' => $album_info['state_id'], 'update_time' => time()]);
                    $album_id = $album_re['id'];
                } else {
                    if ($album_info['path'] && $album_info['path'] != '/images/nocover.jpg') {
                        $img_save_path = $this->downloadImage($album_info['path']);
                    } else {
                        $img_save_path = '/public/album_img/nocover.jpg';
                    }
                    $last_album_re = Db::name('album')->field('id')->where('cai_type', 1)->order('id desc')->find();
                    if ($last_album_re['id'] < 4000000) {
                        $last_album_id = 4000000;
                    } else {
                        $last_album_id = $last_album_re['id'] + 1;
                    }
                    $album_arr = array(
                        'id' => $last_album_id,
                        'album_name' => $album_info['album_name'],
                        'column_id' => 1,
                        'p_cate_id' => $p_cate_id,
                        'checks_id' => 1,
                        'img' => $img_save_path,
                        'content' => $album_info['intro'],
                        'update_time' => strtotime($album_info['lastUpdate']),
                        'state_id' => $album_info['state_id'],
                        'quality_id' => 2,
                        'album_info' => $album_info['intro'],
                        'letter' => $this->getFirstCharter((string)$album_info['album_name']),
                        'words_num' => $album_info['words_num'],
                        'cai_type' => 1,
                        'q_id' => $v,
                        'author' => $album_info['author'],
                    );
                    $album_id = Db::name('album')->insertGetId($album_arr);
                }

                //获取章节列表


                $chapter_info = QueryList::Query('https://www.qiushuzw.com/t/' . $v . '/', array(
                    'chapter_name' => ['.book_con_list:eq(1) ul li a', 'text'],
                    'chapter_url' => ['.book_con_list:eq(1) ul li a', 'href'],
                ))->data;
                $last_chapter_re = Db::name('plug_files')->field('title')->where(array('album_id' => $album_id))->order('id desc')->find();//exit();
                $splice_key = '';
                foreach ($chapter_info as $key => $val) {
                    if ($val['chapter_name'] == $last_chapter_re['title']) {
                        $splice_key = $key;
                        continue;
                    }
                }
                if (strlen($splice_key) > 0) {
                    $chapter_info = array_splice($chapter_info, $splice_key + 1, count($chapter_info));
                }
                if ($album_id > 45999) {
                    $new_album_id = $album_id - 45999;
                } else {
                    $new_album_id = $album_id;
                }
                $chu_album_id = floor($album_id / 1000);
                foreach ($chapter_info as $key => $val) {
                  
                    $content_info = QueryList::Query('https://www.qiushuzw.com/t/' . $v . '/' . $val['chapter_url'], array(
                        'content' => ['.book_content', 'text'],
                    ))->data;
                    if ($content_info) {
                       
                        $splice_key = (int)$splice_key;
                        if ($splice_key > 0) {
                            $num = $splice_key + $key + 2;
                        } else {
                            if ($last_chapter_re) {
                                $num = $splice_key + $key + 2;
                            } else {
                                $num = $splice_key + $key + 1;
                            }
                        }
                        $file_path = ROOT_PATH . '/public/album_txt/' . $chu_album_id . '/' . $new_album_id;
                        
                        if (!file_exists($file_path)) {
                            mkdir($file_path);
                        }
                       
                        $path = ROOT_PATH . '/public/album_txt/' . $chu_album_id . '/' . $new_album_id . '/' . $num . '.txt';
                        $path1 = '/public/album_txt/' . $chu_album_id . '/' . $new_album_id . '/' . $num . '.txt';
                        $myfile = fopen($path, "w") or die("Unable to open file!");
                        fwrite($myfile, $content_info[0]['content']);
                        fclose($myfile);
                        $chapter_arr = array(
                            'uptime' => date('Y-m-d H:i:s', time()),
                            'title' => $val['chapter_name'],
                            'album_id' => $album_id,
                            'yuebi' => 0,
                            'uptime1' => date('Y-m-d', time()),
                            'uptime2' => date('Y-m-d', time()),
                            'path' => $path1,
                            'words_num' => $this->utf8_strlen($content_info[0]['content']),
                            'checks_id' => 1,
                            'chapter_type' => 0,
                            'file_size' => $this->Tokb(filesize(ROOT_PATH . $path1)),
                        );
                        Db::name('plug_files')->insert($chapter_arr);
                    }


                }

            }

        }

    }

    //求书网更新章节
    public function qs_chapter()
    {
        set_time_limit(0);
        ini_set('memory_limit', '30768M');
        /* require_once dirname(__FILE__) . DIRECTORY_SEPARATOR.'../../../thinkphp/library/QL/QueryList.php';
         require_once dirname(__FILE__) . DIRECTORY_SEPARATOR.'../../../thinkphp/library/QL/phpQuery.php';*/
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../model/QueryList.php';
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../model/phpQuery.php';
        // require_once dirname(__FILE__) . DIRECTORY_SEPARATOR.'../model/CurlMulti.php';
        $t1 = microtime(true);

        //载入视图页面
        //采集某页面所有的图片
        //书本详情
        //$album_id=1;
        //$start=input('start')?input('start'):1;
        //$end=input('end')?input('end'):5000;
        $start_time = strtotime(date('Y-m-d', time()) . '00:00:00');//,'state_id'=>1
        $start_time1 = strtotime(date('Y-m-d', time()) . '00:00:00') - 129600;
        $p = input('page') ? input('page') : 1;
        $pagesize = 1000;
        $start = ($p - 1) * $pagesize;
        $album_list = Db::name('album')->field('id,q_id,state_id')->where(array('cai_type' => 1, 'state_id' => 1))->where('q_id>0 and update_time<' . $start_time1)->limit($start, $pagesize)->select();
        if (!$album_list) {
            $album_list = Db::name('album')->field('id,q_id,state_id')->where(array('cai_type' => 1, 'state_id' => 1))->where('q_id>0 and update_time<' . $start_time)->limit($start, $pagesize)->select();
        }
		
        //$album_list=Db::name('album')->field('id,q_id,state_id')->where(array('cai_type'=>1,'state_id'=>1))->where('q_id>0 and update_time<'.$start_time)->limit($start, $pagesize)->select();
        //$album_list=Db::name('album')->field('id,q_id')->where(array('state_id' => 1))->where('q_id>0')->limit(0,1000)->select();
        //echo "<pre>";print_r($album_list);exit();
        //for($i=$start;$i<$end;$i++){
        foreach ($album_list as $k => $v) {

            $album_info = QueryList::Query('https://www.qiushuzw.com/txt' . $v['q_id'] . '/', array(
                'album_name' => ['.tit1 h1', 'text'],
                'author' => ['.date span:eq(0)', 'text'],
                'words_num' => ['#con_ph1_2 td:eq(1)', 'text'],
                'state_name' => ['#con_ph1_2 td:eq(2)', 'text'],
            ))->data;


            if ($album_info) {
                $album_info = $album_info[0];
                $album_info['author'] = str_replace('小说作者：', '', $album_info['author']);
                $album_info['words_num'] = str_replace('完成字数：', '', $album_info['words_num']);
                $album_info['state_name'] = str_replace('写作进程：', '', $album_info['state_name']);
                if ($album_info['state_name'] == '连载中') {
                    $album_info['state_id'] = 1;
                } else {
                    $album_info['state_id'] = 2;
                }
                //$album_re=Db::name('album')->field('id')->where(array('author' => $album_info['author'], 'album_name' => $album_info['album_name']))->find();
                // $album_re=1;
                //if($album_re){
                Db::name('album')->where(array('id' => $v['id']))->update(['words_num' => $album_info['words_num'], 'state_id' => $album_info['state_id'], 'update_time' => time()]);
                //	$chapter_count=Db::name('plug_files')->where('album_id',$v['id'])->count();
                //if($chapter_count>0 && $v['state_id']==2){

                //}else{
                //获取章节列表
                if ($v['id'] > 45999) {
                    $new_album_id = $v['id'] - 45999;
                } else {
                    $new_album_id = $v['id'];
                }
                $chu_album_id = floor($v['id'] / 1000);
                $chapter_info = QueryList::Query('https://www.qiushuzw.com/t/' . $v['q_id'] . '/', array(
                    'chapter_name' => ['.book_con_list:eq(1) ul li a', 'text'],
                    'chapter_url' => ['.book_con_list:eq(1) ul li a', 'href'],
                ))->data;
                $last_chapter_re = Db::name('plug_files')->field('title')->where(array('album_id' => $v['id']))->order('id desc')->find();//exit();
                $splice_key = '';
                foreach ($chapter_info as $key => $val) {
                    if ($val['chapter_name'] == $last_chapter_re['title']) {
                        $splice_key = $key;
                        continue;
                    }
                }
                if (strlen($splice_key) > 0) {
                    $chapter_info = array_splice($chapter_info, $splice_key + 1, count($chapter_info));
                }
                //echo "<pre>";print_r($chapter_info);echo $v['id'].'<br>';echo $splice_key;exit();
                foreach ($chapter_info as $key => $val) {
                    //$chapter_re=Db::name('plug_files')->field('id')->where(array('title'=>$val['chapter_name'],'album_id'=>$v['id']))->find();
                    //if(!$chapter_re){
                    $content_info = QueryList::Query('https://www.qiushuzw.com/t/' . $v['q_id'] . '/' . $val['chapter_url'], array(
                        'content' => ['.book_content', 'text'],
                    ))->data;
                    if ($content_info) {
                        //$num=rand(100000,999999);
                        $splice_key = (int)$splice_key;
                        if ($splice_key > 0) {
                            $num = $splice_key + $key + 2;
                        } else {
                            if ($last_chapter_re) {
                                $num = $splice_key + $key + 2;
                            } else {
                                $num = $splice_key + $key + 1;
                            }
                        }


                        $file_path = ROOT_PATH . '/public/album_txt/' . $chu_album_id . '/' . $new_album_id;
                        if (!file_exists($file_path)) {
                            mkdir($file_path);
                        }
                        // $rand=time().rand(10000,99999);
                        $path = ROOT_PATH . '/public/album_txt/' . $chu_album_id . '/' . $new_album_id . '/' . $num . '.txt';
                        $path1 = '/public/album_txt/' . $chu_album_id . '/' . $new_album_id . '/' . $num . '.txt';
                        $myfile = fopen($path, "w") or die("Unable to open file!");
                        fwrite($myfile, $content_info[0]['content']);
                        fclose($myfile);
                        $chapter_arr = array(
                            'uptime' => date('Y-m-d H:i:s', time()),
                            'title' => $val['chapter_name'],
                            'album_id' => $v['id'],
                            'yuebi' => 0,
                            'uptime1' => date('Y-m-d', time()),
                            'uptime2' => date('Y-m-d', time()),
                            'path' => $path1,
                            'words_num' => $this->utf8_strlen($content_info[0]['content']),
                            'checks_id' => 1,
                            'chapter_type' => 0,
                            'file_size' => $this->Tokb(filesize(ROOT_PATH . $path1)),
                        );
                        Db::name('plug_files')->insert($chapter_arr);
                    }

                    // }

                }
                //}
                //echo "<pre>";print_r($chapter_info);
                //}

            }

        }

    }

    //ABC小说网采集
    public function abc_album()
    {
        set_time_limit(0);
        ini_set('memory_limit', '30768M');
        
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../model/QueryList.php';
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../model/phpQuery.php';
      
        $t1 = microtime(true);
        //载入视图页面
        //采集某页面所有的图片
        //书本详情
        $album_str = input('album_str');
        $album_id_arr = explode(',', $album_str);
        foreach ($album_id_arr as $k => $v) {
            $album_info = QueryList::Query('https://www.abcxs.com/book/' . $v . '/', array(
                'album_name' => ['#info h1', 'text'],
                'author' => ['#info p:eq(0)', 'text'],
                'state_name' => ['#info p:eq(1)', 'text'],
                'intro' => ['#intro p:eq(0)', 'text'],
                'path' => ['#fmimg img', 'src'],
                'cate_name' => ['meta:eq(11)', 'content'],
                'lastUpdate' => ['#info p:eq(3)', 'text'],
            ))->data;

            if ($album_info) {
                $album_info = $album_info[0];
                $album_info['album_name'] = mb_convert_encoding($album_info['album_name'], 'utf-8', 'gbk');
                $album_info['author'] = mb_convert_encoding($album_info['author'], 'utf-8', 'gbk');
                $album_info['state_name'] = mb_convert_encoding($album_info['state_name'], 'utf-8', 'gbk');
                $album_info['intro'] = mb_convert_encoding($album_info['intro'], 'utf-8', 'gbk');
                $album_info['lastUpdate'] = mb_convert_encoding($album_info['lastUpdate'], 'utf-8', 'gbk');
                if ($album_info['author']) {
                    $author_arr = explode('：', $album_info['author']);
                    $album_info['author'] = $author_arr[1];
                }
                if ($album_info['state_name']) {
                    $state_arr = explode('：', $album_info['state_name']);
                    $album_info['state_name'] = $state_arr[1];
                }
                if ($album_info['lastUpdate']) {
                    $lastUpdate_arr = explode('：', $album_info['lastUpdate']);
                    $album_info['lastUpdate'] = $lastUpdate_arr[1];
                }
                if ($album_info['state_name'] == '连载') {
                    $album_info['state_id'] = 1;
                } else {
                    $album_info['state_id'] = 2;
                }
                switch ($album_info['cate_name']) {
                    case '玄幻奇幻':
                        $p_cate_id = 6;
                        break;
                    case '武侠修真':
                        $p_cate_id = 8;
                        break;
                    case '都市言情':
                        $p_cate_id = 10;
                        break;
                    case '历史军事':
                        $p_cate_id = 13;
                        break;
                    case '侦探推理':
                        $p_cate_id = 11;
                        break;
                    case '网游竞技':
                        $p_cate_id = 14;
                        break;
                    case '科幻小说':
                        $p_cate_id = 16;
                        break;
                    case '恐怖灵异':
                        $p_cate_id = 17;
                        break;
                    case '其他小说':
                        $p_cate_id = 18;
                        break;
                    default:
                        $p_cate_id = 18;
                        break;
                }

                $album_re = Db::name('album')->field('id')->where(array('author' => $album_info['author'], 'album_name' => $album_info['album_name']))->find();
               
                if ($album_re) {
                    Db::name('album')->where(array('id' => $album_re['id']))->update(['state_id' => $album_info['state_id'], 'a_id' => $v, 'update_time' => time()]);
                    $album_id = $album_re['id'];
                } else {
                    if ($album_info['path'] && $album_info['path'] != '/images/nocover.jpg') {
                        $img_save_path = $this->downloadImage('https://www.abcxs.com' . $album_info['path']);
                    } else {
                        $img_save_path = '/public/album_img/nocover.jpg';
                    }
                   
                    $album_arr = array(
                        
                        'album_name' => $album_info['album_name'],
                        'column_id' => 1,
                        'p_cate_id' => $p_cate_id,
                        'checks_id' => 1,
                        'img' => $img_save_path,
                        'content' => $album_info['intro'],
                        'update_time' => strtotime($album_info['lastUpdate']),
                        'state_id' => $album_info['state_id'],
                        'quality_id' => 2,
                        'album_info' => $album_info['intro'],
                        'letter' => $this->getFirstCharter((string)$album_info['album_name']),
                        'words_num' => 0,
                        'cai_type' => 3,
                        'a_id' => $v,
                        'author' => $album_info['author'],
                    );
                    $album_id = Db::name('album')->insertGetId($album_arr);
                }
                //获取章节列表
                $str = $this->getHtml('https://www.abcxs.com/book/' . $v . '/');
                $chapter_info = QueryList::Query($str, array(
                    'chapter_name' => ['.listmain dl dd a', 'text'],
                    'chapter_url' => ['.listmain dl dd a', 'href'],
                ), '', 'UTF-8', 'GB2312')->data;
               
                if (count($chapter_info) / 2 < 6) {
                    $num = count($chapter_info) / 2;
                    for ($q = 0; $q < $num; $q++) {
                        unset($chapter_info[$q]);
                    }
                } else {
                    for ($q = 0; $q < 6; $q++) {
                        unset($chapter_info[$q]);
                    }
                }
                $chapter_info = array_merge($chapter_info);
                $sum_words_num = 0;
                $last_chapter_re = Db::name('plug_files')->field('title')->where(array('album_id' => $album_id))->order('id desc')->find();//exit();
                $splice_key = '';
                foreach ($chapter_info as $key => $val) {
                    if ($val['chapter_name'] == $last_chapter_re['title']) {
                        $splice_key = $key;
                        continue;
                    }
                }
                if (strlen($splice_key) > 0) {
                    $chapter_info = array_splice($chapter_info, $splice_key + 1, count($chapter_info));
                }
                
                foreach ($chapter_info as $key => $val) {
                   
                    $splice_key = (int)$splice_key;
                    if ($splice_key > 0) {
                        $num = $album_id . '_' . ($splice_key + $key + 2);
                    } else {
                        if ($last_chapter_re) {
                            $num = $album_id . '_' . ($splice_key + $key + 2);
                        } else {
                            $num = $album_id . '_' . ($splice_key + $key + 1);
                        }
                    }


                    
                    $content_info = QueryList::Query('https://www.abcxs.com' . $val['chapter_url'], array(
                        'content' => ['.showtxt', 'text'],
                    ))->data;
                    if ($content_info) {

                        $content = $content_info[0]['content'];


                        $file_path = ROOT_PATH . '/public/album_txt/' . $album_id;
                        if (!file_exists($file_path)) {
                            mkdir($file_path);
                        }
                       
                        $path = ROOT_PATH . '/public/album_txt/' . $album_id . '/' . $num . '.txt';
                        $path1 = '/public/album_txt/' . $album_id . '/' . $num . '.txt';
                        $myfile = fopen($path, "w") or die("Unable to open file!");
                        fwrite($myfile, $content);
                        fclose($myfile);
                        $chapter_arr = array(
                            'uptime' => date('Y-m-d H:i:s', time()),
                            'title' => $val['chapter_name'],
                            'album_id' => $album_id,
                            'yuebi' => 0,
                            'uptime1' => date('Y-m-d', time()),
                            'uptime2' => date('Y-m-d', time()),
                            'path' => $path1,
                            'checks_id' => 1,
                            'words_num' => $this->utf8_strlen($content),
                            'chapter_type' => 0,
                            'file_size' => $this->Tokb(filesize(ROOT_PATH . $path1)),
                        );
                        Db::name('plug_files')->insert($chapter_arr);
                        $sum_words_num += $this->utf8_strlen($content);
                    }

                }

               
                Db::name('album')->where('id', $album_id)->setInc('words_num', $sum_words_num);
            
            }

            
        }
       
    }

    //abc小说自动更新
    public function abc_chapter()
    {
        set_time_limit(0);
        ini_set('memory_limit', '30768M');
       
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../model/QueryList.php';
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../model/phpQuery.php';
       
        $t1 = microtime(true);
        //载入视图页面
        //采集某页面所有的图片
        //书本详情
        $start_time = strtotime(date('Y-m-d', time()) . '00:00:00');
        $start_time1 = strtotime(date('Y-m-d', time()) . '00:00:00') - 86400;
        $p = input('page') ? input('page') : 1;
        $pagesize = 1000;
        $start = ($p - 1) * $pagesize;
        $album_list = Db::name('album')->field('id,a_id')->where(array('cai_type' => 3, 'state_id' => 1))->where('update_time<' . $start_time1)->limit($start, $pagesize)->select();
        if (!$album_list) {
            $album_list = Db::name('album')->field('id,a_id')->where(array('cai_type' => 3, 'state_id' => 1))->where('update_time<' . $start_time)->limit($start, $pagesize)->select();
        }

       
        foreach ($album_list as $k => $v) {
            $abc_id = $v['a_id'];
            $album_id = $v['id'];
            //获取章节列表
            $str = $this->getHtml('https://www.abcxs.com/book/' . $abc_id . '/');
            $chapter_info = QueryList::Query($str, array(
                'chapter_name' => ['.listmain dl dd a', 'text'],
                'chapter_url' => ['.listmain dl dd a', 'href'],
            ), '', 'UTF-8', 'GB2312')->data;
            if (count($chapter_info) / 2 < 6) {
                $num = count($chapter_info) / 2;
                for ($q = 0; $q < $num; $q++) {
                    unset($chapter_info[$q]);
                }
            } else {
                for ($q = 0; $q < 6; $q++) {
                    unset($chapter_info[$q]);
                }
            }
            $chapter_info = array_merge($chapter_info);
            $sum_words_num = 0;
            $last_chapter_re = Db::name('plug_files')->field('title')->where(array('album_id' => $album_id))->order('id desc')->find();//exit();
            $splice_key = '';
            foreach ($chapter_info as $key => $val) {
                if ($val['chapter_name'] == $last_chapter_re['title']) {
                    $splice_key = $key;
                    continue;
                }
            }
            if (strlen($splice_key) > 0) {
                $chapter_info = array_splice($chapter_info, $splice_key + 1, count($chapter_info));
            }
           
			if((int)$splice_key>0){
				 $ckeck_repeat=Db::name('plug_files')->field('id')->where(array('album_id'=>$album_id,'path'=>'/public/album_txt/' . $album_id . '/' . $album_id . '_' . ($splice_key+2) . '.txt'))->find();
			}else{
				$ckeck_repeat='';
			}
           
            if($ckeck_repeat){
                $this->del_more($album_id);
				Db::name('plug_files')->where(array('album_id'=>$album_id))->delete();
            }else{
                foreach ($chapter_info as $key => $val) {
                   
                    $splice_key = (int)$splice_key;
                    if ($splice_key > 0) {
                        $num = $album_id . '_' . ($splice_key + $key + 2);
                    } else {
                        if ($last_chapter_re) {
                            $num = $album_id . '_' . ($splice_key + $key + 2);
                        } else {
                            $num = $album_id . '_' . ($splice_key + $key + 1);
                        }
                    }
                    $content_info = QueryList::Query('https://www.abcxs.com' . $val['chapter_url'], array(
                        'content' => ['.showtxt', 'text'],
                    ))->data;
                    if ($content_info) {
                        $content = $content_info[0]['content'];


                        $file_path = ROOT_PATH . '/public/album_txt/' . $album_id;
                        if (!file_exists($file_path)) {
                            mkdir($file_path);
                        }
                       
                        $path = ROOT_PATH . '/public/album_txt/' . $album_id . '/' . $num . '.txt';
                        $path1 = '/public/album_txt/' . $album_id . '/' . $num . '.txt';

                        if (!file_exists($path)) {
                            $myfile = fopen($path, "w") or die("Unable to open file!");
                            fwrite($myfile, $content);
                            fclose($myfile);
                        }
                        $chapter_arr = array(
                            'uptime' => date('Y-m-d H:i:s', time()),
                            'title' => $val['chapter_name'],
                            'album_id' => $album_id,
                            'yuebi' => 0,
                            'checks_id' => 1,
                            'uptime1' => date('Y-m-d', time()),
                            'uptime2' => date('Y-m-d', time()),
                            'path' => $path1,
                            'words_num' => $this->utf8_strlen($content),
                            'chapter_type' => 0,
                            'file_size' => $this->Tokb(filesize(ROOT_PATH . $path1)),
                        );
                        Db::name('plug_files')->insert($chapter_arr);
                        $sum_words_num += $this->utf8_strlen($content);
                    }

                   

                }
                Db::name('album')->where('id', $album_id)->update(['update_time' => time()]);
                Db::name('album')->where('id', $album_id)->setInc('words_num', $sum_words_num);
            }

           
        }
    }

//八一中文网小说自动更新
    public function by_chapter()
    {
        set_time_limit(0);
        ini_set('memory_limit', '30768M');
        
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../model/QueryList.php';
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../model/phpQuery.php';
       
        $t1 = microtime(true);
        //载入视图页面
        //采集某页面所有的图片
        //书本详情
        
        $start_time = strtotime(date('Y-m-d', time()) . '00:00:00');
        $start_time1 = strtotime(date('Y-m-d', time()) . '00:00:00') - 129600;
        $p = input('page') ? input('page') : 1;
        $pagesize = 1000;
        $start = ($p - 1) * $pagesize;
        $album_list = Db::name('album')->field('id,b_id')->where(array('cai_type' => 4, 'state_id' => 1))->where('update_time<' . $start_time1)->limit($start, $pagesize)->select();
        if (!$album_list) {
            $album_list = Db::name('album')->field('id,b_id')->where(array('cai_type' => 4, 'state_id' => 1))->where('update_time<' . $start_time)->limit($start, $pagesize)->select();
        }

        
        foreach ($album_list as $k => $v) {
            $abc_id = $v['b_id'];
            $album_id = $v['id'];
            //获取章节列表
            $str = $this->getHtml('https://www.zwdu.com/book/' . $abc_id . '/');
            $chapter_info = QueryList::Query($str, array(
                'chapter_name' => ['#list dl dd a', 'text'],
                'chapter_url' => ['#list dl dd a', 'href'],
            ), '', 'UTF-8', 'GB2312')->data;
           

            $sum_words_num = 0;
            $last_chapter_re = Db::name('plug_files')->field('title')->where(array('album_id' => $album_id))->order('id desc')->find();//exit();
            $splice_key = '';
            foreach ($chapter_info as $key => $val) {
                if ($val['chapter_name'] == $last_chapter_re['title']) {
                    $splice_key = $key;
                    continue;
                }
            }
            if (strlen($splice_key) > 0) {
                $chapter_info = array_splice($chapter_info, $splice_key + 1, count($chapter_info));
            }
            
            if((int)$splice_key>0){
				 $ckeck_repeat=Db::name('plug_files')->field('id')->where(array('album_id'=>$album_id,'path'=>'/public/album_txt/' . $album_id . '/' . $album_id . '_' . ($splice_key+2) . '.txt'))->find();
			}else{
				$ckeck_repeat='';
			}
        
            if($ckeck_repeat){
                $this->del_more($album_id);
				Db::name('plug_files')->where(array('album_id'=>$album_id))->delete();
            }else{
            foreach ($chapter_info as $key => $val) {
               
                $splice_key = (int)$splice_key;
                if ($splice_key > 0) {
                    $num = $album_id . '_' . ($splice_key + $key + 2);
                } else {
                    if ($last_chapter_re) {
                        $num = $album_id . '_' . ($splice_key + $key + 2);
                    } else {
                        $num = $album_id . '_' . ($splice_key + $key + 1);
                    }
                }
                
                $content_info = QueryList::Query('https://www.zwdu.com' . $val['chapter_url'], array(
                    'content' => ['#content', 'text'],
                ))->data;
                if ($content_info) {
                    $content = $content_info[0]['content'];

                    $file_path = ROOT_PATH . '/public/album_txt/' . $album_id;
                    if (!file_exists($file_path)) {
                        mkdir($file_path);
                    }
                   
                    $path = ROOT_PATH . '/public/album_txt/' . $album_id . '/' . $num . '.txt';
                    $path1 = '/public/album_txt/' . $album_id . '/' . $num . '.txt';
                    if (!file_exists($path)) {
                        $myfile = fopen($path, "w") or die("Unable to open file!");
                        fwrite($myfile, $content);
                        fclose($myfile);
                    }

                    $chapter_arr = array(
                        'uptime' => date('Y-m-d H:i:s', time()),
                        'title' => $val['chapter_name'],
                        'album_id' => $album_id,
                        'yuebi' => 0,
                        'uptime1' => date('Y-m-d', time()),
                        'uptime2' => date('Y-m-d', time()),
                        'path' => $path1,
                        'words_num' => $this->utf8_strlen($content),
                        'chapter_type' => 0,
                        'checks_id' => 1,
                        'file_size' => $this->Tokb(filesize(ROOT_PATH . $path1)),
                    );
                    Db::name('plug_files')->insert($chapter_arr);
                    $sum_words_num += $this->utf8_strlen($content);
                }

                

            }
            Db::name('album')->where('id', $album_id)->update(['update_time' => time()]);
            Db::name('album')->where('id', $album_id)->setInc('words_num', $sum_words_num);
        }
           
        }
    }

    function getHtml($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22");
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip'); //加入gzip解析
        $output = curl_exec($ch);
        // $info = curl_getinfo($ch);

        curl_close($ch);

        return $output;
    }

    //八一中文网小说网采集
    public function by_album()
    {
        set_time_limit(0);
        ini_set('memory_limit', '30768M');
        /* require_once dirname(__FILE__) . DIRECTORY_SEPARATOR.'../../../thinkphp/library/QL/QueryList.php';
         require_once dirname(__FILE__) . DIRECTORY_SEPARATOR.'../../../thinkphp/library/QL/phpQuery.php';*/
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../model/QueryList.php';
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '../model/phpQuery.php';
        // require_once dirname(__FILE__) . DIRECTORY_SEPARATOR.'../model/CurlMulti.php';
        $t1 = microtime(true);
        //载入视图页面
        //采集某页面所有的图片
        //书本详情
        $album_str = input('album_str');
        $album_id_arr = explode(',', $album_str);
        foreach ($album_id_arr as $k => $v) {
            $album_info = QueryList::Query('https://www.zwdu.com/book/' . $v . '/', array(
                'album_name' => ['#info h1', 'text'],
                'author' => ['#info p:eq(0)', 'text'],
                'state_name' => ['#info p:eq(1)', 'text'],
                'intro' => ['#intro p:eq(0)', 'text'],
                'path' => ['#fmimg img', 'src'],
                'cate_name' => ['meta:eq(11)', 'content'],
                'lastUpdate' => ['#info p:eq(3)', 'text'],
            ))->data;

            if ($album_info) {
                $album_info = $album_info[0];
                $album_info['album_name'] = mb_convert_encoding($album_info['album_name'], 'utf-8', 'gbk');
                $album_info['author'] = mb_convert_encoding($album_info['author'], 'utf-8', 'gbk');
                $album_info['state_name'] = mb_convert_encoding($album_info['state_name'], 'utf-8', 'gbk');
                $album_info['intro'] = mb_convert_encoding($album_info['intro'], 'utf-8', 'gbk');
                $album_info['lastUpdate'] = mb_convert_encoding($album_info['lastUpdate'], 'utf-8', 'gbk');
                if ($album_info['author']) {
                    $author_arr = explode('：', $album_info['author']);
                    $album_info['author'] = $author_arr[1];
                }
                if ($album_info['state_name']) {
                    $state_arr = explode('：', $album_info['state_name']);
                    $album_info['state_name'] = $state_arr[1];
                }
                if ($album_info['lastUpdate']) {
                    $lastUpdate_arr = explode('：', $album_info['lastUpdate']);
                    $album_info['lastUpdate'] = $lastUpdate_arr[1];
                }
                if ($album_info['state_name'] == '连载中,加入书架,直达底部') {
                    $album_info['state_id'] = 1;
                } else {
                    $album_info['state_id'] = 2;
                }
                switch ($album_info['cate_name']) {
                    case '玄幻小说':
                        $p_cate_id = 6;
                        break;
                    case '修真小说':
                        $p_cate_id = 8;
                        break;
                    case '都市小说':
                        $p_cate_id = 10;
                        break;
                    case '历史小说':
                        $p_cate_id = 13;
                        break;
                    case '言情小说':
                        $p_cate_id = 10;
                        break;
                    case '网游小说':
                        $p_cate_id = 14;
                        break;
                    case '科幻小说':
                        $p_cate_id = 16;
                        break;
                    case '恐怖灵异':
                        $p_cate_id = 17;
                        break;
                    case '其他小说':
                        $p_cate_id = 18;
                        break;
                    default:
                        $p_cate_id = 18;
                        break;
                }

                $album_re = Db::name('album')->field('id')->where(array('author' => $album_info['author'], 'album_name' => $album_info['album_name']))->find();
                // $album_re=1;
                if ($album_re) {
                    Db::name('album')->where(array('id' => $album_re['id']))->update(['state_id' => $album_info['state_id'], 'a_id' => $v, 'update_time' => time()]);
                    $album_id = $album_re['id'];
                } else {
                    if ($album_info['path'] && $album_info['path'] != '/images/nocover.jpg') {
                        $img_save_path = $this->downloadImage('http://www.abcxs.com' . $album_info['path']);
                    } else {
                        $img_save_path = '/public/album_img/nocover.jpg';
                    }
                    /*$last_album_re = Db::name('album')->field('id')->where('cai_type', 3)->order('id desc')->find();
                    if ($last_album_re['id'] < 3000000) {
                        $last_album_id = 3000000;
                    } else {
                        $last_album_id = $last_album_re['id'] + 1;
                    }*/
                    $album_arr = array(
                        //'id' => $last_album_id,
                        'album_name' => $album_info['album_name'],
                        'column_id' => 1,
                        'p_cate_id' => $p_cate_id,
                        'checks_id' => 1,
                        'img' => $img_save_path,
                        'content' => $album_info['intro'],
                        'update_time' => strtotime($album_info['lastUpdate']),
                        'state_id' => $album_info['state_id'],
                        'quality_id' => 2,
                        'album_info' => $album_info['intro'],
                        'letter' => $this->getFirstCharter((string)$album_info['album_name']),
                        'words_num' => 0,
                        'cai_type' => 4,
                        'b_id' => $v,
                        'author' => $album_info['author'],
                    );
                    $album_id = Db::name('album')->insertGetId($album_arr);
                }
                //获取章节列表
                $str = $this->getHtml('https://www.zwdu.com/book/' . $v . '/');
                $chapter_info = QueryList::Query($str, array(
                    'chapter_name' => ['#list dl dd a', 'text'],
                    'chapter_url' => ['#list dl dd a', 'href'],
                ), '', 'UTF-8', 'GB2312')->data;
                //$chapter_info = array_merge($chapter_info);
                //echo "<pre>";print_r($chapter_info);exit();
                $sum_words_num = 0;
                $last_chapter_re = Db::name('plug_files')->field('title')->where(array('album_id' => $album_id))->order('id desc')->find();//exit();
                $splice_key = '';
                foreach ($chapter_info as $key => $val) {
                    if ($val['chapter_name'] == $last_chapter_re['title']) {
                        $splice_key = $key;
                        continue;
                    }
                }
                if (strlen($splice_key) > 0) {
                    $chapter_info = array_splice($chapter_info, $splice_key + 1, count($chapter_info));
                }
                //$ckeck_repeat=Db::name('plug_files')->field('id')->where(array('album_id'=>$album_id,'path'=>'/public/album_txt/' . $album_id . '/' . $album_id . '_' . ($splice_key+2) . '.txt'))->find();
                //if($ckeck_repeat){
                //    $this->del_more($album_id);
                // }else{
                foreach ($chapter_info as $key => $val) {
                    //$chapter_re = Db::name('plug_files')->field('id')->where(array('title' => $val['chapter_name'], 'album_id' => $album_id))->find();
                    //if (!$chapter_re) {
                    $splice_key = (int)$splice_key;
                    if ($splice_key > 0) {
                        $num = $album_id . '_' . ($splice_key + $key + 2);
                    } else {
                        $num = $album_id . '_' . ($splice_key + $key + 1);
                    }
                    //if(!file_exists(ROOT_PATH . '/public/album_txt/' . $album_id . '/' . $num . '.txt')) {
                    $content_info = QueryList::Query('https://www.zwdu.com' . $val['chapter_url'], array(
                        'content' => ['#content', 'text'],
                    ))->data;

                    if ($content_info) {
                        $content = $content_info[0]['content'];

                        //echo $content;exit();
                        $file_path = ROOT_PATH . '/public/album_txt/' . $album_id;
                        if (!file_exists($file_path)) {
                            mkdir($file_path);
                        }
                        // $rand=time().rand(10000,99999);
                        $path = ROOT_PATH . '/public/album_txt/' . $album_id . '/' . $num . '.txt';
                        $path1 = '/public/album_txt/' . $album_id . '/' . $num . '.txt';
                        $myfile = fopen($path, "w") or die("Unable to open file!");
                        fwrite($myfile, $content);
                        fclose($myfile);
                        $chapter_arr = array(
                            'uptime' => date('Y-m-d H:i:s', time()),
                            'title' => $val['chapter_name'],
                            'album_id' => $album_id,
                            'yuebi' => 0,
                            'uptime1' => date('Y-m-d', time()),
                            'uptime2' => date('Y-m-d', time()),
                            'path' => $path1,
                            'words_num' => $this->utf8_strlen($content),
                            'chapter_type' => 0,
                            'checks_id' => 1,
                            'file_size' => $this->Tokb(filesize(ROOT_PATH . $path1)),
                        );
                        Db::name('plug_files')->insert($chapter_arr);
                        $sum_words_num += $this->utf8_strlen($content);
                    }

                    // }

                    //}

                }
                Db::name('album')->where('id', $album_id)->setInc('words_num', $sum_words_num);
                //echo "<pre>";print_r($chapter_info);
            //}
            }
        }
    }

    function utf8_strlen($string = null)
    {
// 将字符串分解为单元
        preg_match_all("/./us", $string, $match);
// 返回单元个数
        return count($match[0]);
        
    }

    function del_file()
    {
        $album_id = input('album_id');

        $path = ROOT_PATH . '/public/album_txt/' . $album_id . '/';
        //如果是目录则继续
        if (is_dir($path)) {
            //扫描一个文件夹内的所有文件夹和文件并返回数组
            $p = scandir($path);
            foreach ($p as $val) {
                //排除目录中的.和..
                if ($val != "." && $val != "..") {
                    //如果是目录则递归子目录，继续操作
                    if (is_dir($path . $val)) {
                        //子目录中操作删除文件夹和文件
                        deldir($path . $val . '/');
                        //目录清空后删除空文件夹
                        @rmdir($path . $val . '/');
                    } else {
                        //如果是文件直接删除
                        unlink($path . $val);
                    }
                }
            }
        }
    }

    

   

    public function get_cate_id($cate_name)
    {
        $cate_arr = array();
        switch ($cate_name) {
            case '玄幻':
                $cate_arr['id'] = 6;
                $cate_arr['column_id'] = 1;
                break;
            case '奇幻':
                $cate_arr['id'] = 7;
                $cate_arr['column_id'] = 1;
                break;
            case '武侠':
                $cate_arr['id'] = 8;
                $cate_arr['column_id'] = 1;
                break;
            case '仙侠':
                $cate_arr['id'] = 9;
                $cate_arr['column_id'] = 1;
                break;
            case '都市':
                $cate_arr['id'] = 10;
                $cate_arr['column_id'] = 1;
                break;
            case '校园':
                $cate_arr['id'] = 70;
                $cate_arr['column_id'] = 1;
                break;
            case '历史':
                $cate_arr['id'] = 13;
                $cate_arr['column_id'] = 1;
                break;
            case '军事':
                $cate_arr['id'] = 12;
                $cate_arr['column_id'] = 1;
                break;
            case '游戏':
                $cate_arr['id'] = 14;
                $cate_arr['column_id'] = 1;
                break;
            case '竞技':
                $cate_arr['id'] = 15;
                $cate_arr['column_id'] = 1;
                break;
            case '科幻':
                $cate_arr['id'] = 16;
                $cate_arr['column_id'] = 1;
                break;
            case '推理悬疑':
                $cate_arr['id'] = 17;
                $cate_arr['column_id'] = 1;
                break;
            case '恐怖惊悚':
                $cate_arr['id'] = 17;
                $cate_arr['column_id'] = 1;
                break;
            case '有兔怪谈':
                $cate_arr['id'] = 18;
                $cate_arr['column_id'] = 1;
                break;
            case '其他':
                $cate_arr['id'] = 18;
                $cate_arr['column_id'] = 1;
                break;
            case '现代言情':
                $cate_arr['id'] = 22;
                $cate_arr['column_id'] = 2;
                break;
            case '古代言情':
                $cate_arr['id'] = 20;
                $cate_arr['column_id'] = 2;
                break;
            case '幻想言情':
                $cate_arr['id'] = 24;
                $cate_arr['column_id'] = 2;
                break;
            case '青春校园':
                $cate_arr['id'] = 23;
                $cate_arr['column_id'] = 2;
                break;
            case '同人作品':
                $cate_arr['id'] = 29;
                $cate_arr['column_id'] = 2;
                break;
            case '次元专区':
                $cate_arr['id'] = 28;
                $cate_arr['column_id'] = 2;
                break;
            case '耽美':
                $cate_arr['id'] = 28;
                $cate_arr['column_id'] = 2;
                break;
        }
        return $cate_arr;

    }

    public function add_txt($album_id, $content, $num)
    {
        $file_path = ROOT_PATH . '/public/album_txt/' . $album_id;
        if (!file_exists($file_path)) {
            mkdir($file_path);
        }

        $path = ROOT_PATH . '/public/album_txt/' . $album_id . '/' . $num . '.txt';
        $path1 = '/public/album_txt/' . $album_id . '/' . $num . '.txt';
        $myfile = fopen($path, "w") or die("Unable to open file!");
        fwrite($myfile, $content);
        fclose($myfile);
        $data['path'] = $path1;
       
        $data['file_size'] = $this->Tokb(filesize(ROOT_PATH . $data['path']));//获取小说的文件大小
        return $data;
    }

    //获取连载状态
    public function get_fullFlag($album_id)
    {
        $album_url = "http://app.youzibank.com/book/info?bookId=$album_id&userId=";
        $album_info_obj = $this->get_content($album_url);//echo "<pre>";print_r($album_info_obj);//echo $album_info->data[0]->fullFlag; exit();
        if ($album_info_obj->enumCode == 'SUCCESS') {
            $album_info = array();
            if ($album_info_obj->data) {
                $album_info['fullFlag'] = $album_info_obj->data[0]->fullFlag ? $album_info_obj->data[0]->fullFlag : 2;
                $album_info['author'] = $album_info_obj->data[0]->author;
                $album_info['id'] = $album_info_obj->data[0]->id;
                $album_info['intro'] = $album_info_obj->data[0]->intro;
                $album_info['lastUpdate'] = $album_info_obj->data[0]->lastUpdate;
                $album_info['name'] = $album_info_obj->data[0]->name;
                $album_info['clsName'] = $album_info_obj->data[0]->clsName;
                if ($album_info_obj->data[0]->photoPath) {
                    $album_info['photoPath'] = 'http://book.wankouzi.com/book' . $album_info_obj->data[0]->photoPath;
                } else {
                    $album_info['photoPath'] = '';
                }
                $album_info['wordCnt'] = $album_info_obj->data[0]->wordCnt;
            } else {
                $album_info = array();
            }

        } else {
            $album_info = array();
        }
        return $album_info;
    }

    public function get_content($url)
    {
        $headers[] = 'Seq: 11111111111111111111111111111111';


        $ch = curl_init();

// 2. 设置选项
        curl_setopt($ch, CURLOPT_URL, $url);  // 设置要抓取的页面地址
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 Safari/537.36");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);              // 抓取结果直接返回（如果为0，则直接输出内容到页面）
        curl_setopt($ch, CURLOPT_HEADER, 0);                      // 不需要页面的HTTP头
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
// 3. 执行并获取HTML文档内容，可用echo输出内容
        $output = curl_exec($ch);

// 4. 释放curl句柄
        curl_close($ch);
        return json_decode($output);
    }

    //下载图片
    public function downloadImage($url, $path = 'public/album_img/')
    {
        $ch = curl_init();
        if (substr($url, 0, 5) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $file = curl_exec($ch);
        curl_close($ch);

        return $this->saveAsImage($url, $file, $path);
    }

    function saveAsImage($url, $file, $path)
    {
        $filename = pathinfo($url, PATHINFO_BASENAME);
        $time = date('Ymd', time());
        $file_path = dirname($_SERVER['SCRIPT_FILENAME']) . '/public/album_img/' . "$time";

        if (!file_exists($file_path)) {
            mkdir($file_path);
        }

        $resource = fopen($file_path . '/' . $filename, 'a');
        fwrite($resource, $file);
        fclose($resource);
        return '/public/album_img/' . $time . '/' . $filename;
    }

    function getFirstCharter($str)
    {
        if (empty($str)) {
            return '';
        }
        $str = preg_replace('/\d*/', '', $str);
        if ($str) {
            $fchar = ord($str{0});
            if ($fchar >= ord('A') && $fchar <= ord('z')) return strtoupper($str{0});
            $s1 = @iconv('UTF-8', 'gbk//IGNORE’', $str);
            $s2 = @iconv('GBK', 'UTF-8//IGNORE’', $s1);
            $s = $s2 == $str ? $s1 : $str;
            $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
            if ($asc >= -20319 && $asc <= -20284) return 'A';
            if ($asc >= -20283 && $asc <= -19776) return 'B';
            if ($asc >= -19775 && $asc <= -19219) return 'C';
            if ($asc >= -19218 && $asc <= -18711) return 'D';
            if ($asc >= -18710 && $asc <= -18527) return 'E';
            if ($asc >= -18526 && $asc <= -18240) return 'F';
            if ($asc >= -18239 && $asc <= -17923) return 'G';
            if ($asc >= -17922 && $asc <= -17418) return 'H';
            if ($asc >= -17417 && $asc <= -16475) return 'J';
            if ($asc >= -16474 && $asc <= -16213) return 'K';
            if ($asc >= -16212 && $asc <= -15641) return 'L';
            if ($asc >= -15640 && $asc <= -15166) return 'M';
            if ($asc >= -15165 && $asc <= -14923) return 'N';
            if ($asc >= -14922 && $asc <= -14915) return 'O';
            if ($asc >= -14914 && $asc <= -14631) return 'P';
            if ($asc >= -14630 && $asc <= -14150) return 'Q';
            if ($asc >= -14149 && $asc <= -14091) return 'R';
            if ($asc >= -14090 && $asc <= -13319) return 'S';
            if ($asc >= -13318 && $asc <= -12839) return 'T';
            if ($asc >= -12838 && $asc <= -12557) return 'W';
            if ($asc >= -12556 && $asc <= -11848) return 'X';
            if ($asc >= -11847 && $asc <= -11056) return 'Y';
            if ($asc >= -11055 && $asc <= -10247) return 'Z';
        } else {
            return '';
        }


    }

  

    /**
     * 字节转化
     */
    public function Tokb($size)
    {
        $kb = 1024;// 1KB（Kibibyte，千字节）=1024B，
        $mb = 1024 * $kb; //1MB（Mebibyte，兆字节，简称“兆”）=1024KB，
        $gb = 1024 * $mb; // 1GB（Gigabyte，吉字节，又称“千兆”）=1024MB，
        $tb = 1024 * $gb; // 1TB（Terabyte，万亿字节，太字节）=1024GB，
        $pb = 1024 * $tb; //1PB（Petabyte，千万亿字节，拍字节）=1024TB，
        $fb = 1024 * $pb; //1EB（Exabyte，百亿亿字节，艾字节）=1024PB，
        $zb = 1024 * $fb; //1ZB（Zettabyte，十万亿亿字节，泽字节）= 1024EB，
        $yb = 1024 * $zb; //1YB（Yottabyte，一亿亿亿字节，尧字节）= 1024ZB，
        $bb = 1024 * $yb; //1BB（Brontobyte，一千亿亿亿字节）= 1024YB

        if ($size < $kb) {
            return $size . " B";
        } elseif ($size < $mb) {
            return round($size / $kb, 2) . " KB";
        } elseif ($size < $gb) {
            return round($size / $mb, 2) . " MB";
        } elseif ($size < $tb) {
            return round($size / $gb, 2) . " GB";
        } elseif ($size < $pb) {
            return round($size / $tb, 2) . " TB";
        } elseif ($size < $fb) {
            return round($size / $pb, 2) . " PB";
        } elseif ($size < $zb) {
            return round($size / $fb, 2) . " EB";
        } elseif ($size < $yb) {
            return round($size / $zb, 2) . " ZB";
        } else {
            return round($size / $bb, 2) . " YB";
        }

    }
}
