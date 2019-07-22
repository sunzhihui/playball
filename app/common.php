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

/**
 * Ӧ�ù������������ļ�
 */

use think\Db;
use think\Response;
use think\exception\HttpResponseException;
use app\common\logic\File as LogicFile;


// +---------------------------------------------------------------------+
// | ϵͳ��غ���
// +---------------------------------------------------------------------+

/**
 * ����û��Ƿ��¼
 * @return integer 0-δ��¼������0-��ǰ��¼�û�ID
 */
function is_login()
{
    
    $member = session('member_auth');
    
    if (empty($member)) {
        
        return DATA_DISABLE;
    } else {
        
        return session('member_auth_sign') == data_auth_sign($member) ? $member['member_id'] : DATA_DISABLE;
    }
}

/**
 * ϵͳ�ǳ���MD5���ܷ���
 * @param  string $str Ҫ���ܵ��ַ���
 * @return string 
 */
function data_md5($str, $key = 'OneBase')
{
    return '' === $str ? '' : md5(sha1($str) . $key);
}

/**
 * ʹ������ĺ�����ϵͳ����KEY����ַ�������
 * @param  string $str Ҫ���ܵ��ַ���
 * @return string 
 */
function data_md5_key($str, $key = '')
{
    
    if (is_array($str)) {
        
        ksort($str);

        $data = http_build_query($str);
        
    } else {
        
        $data = (string) $str;
    }

    return empty($key) ? data_md5($data, SYS_ENCRYPT_KEY) : data_md5($data, $key);
}

/**
 * ����ǩ����֤
 * @param  array  $data ����֤������
 * @return string       ǩ��
 */
function data_auth_sign($data)
{
    
    // �������ͼ��
    if (!is_array($data)) {
        
        $data = (array)$data;
    }
    
    // ����
    ksort($data);
    
    // url���벢����query�ַ���
    $code = http_build_query($data);
    
    // ����ǩ��
    $sign = sha1($code);
    
    return $sign;
}

/**
 * ��⵱ǰ�û��Ƿ�Ϊ����Ա
 * @return boolean true-����Ա��false-�ǹ���Ա
 */
function is_administrator($member_id = null)
{
    
    $return_id = is_null($member_id) ? is_login() : $member_id;
    
    return $return_id && (intval($return_id) === SYS_ADMINISTRATOR_ID);
}

/**
 * ��ȡ��������
 */
function get_sington_object($object_name = '', $class = null)
{

    $request = request();

    $request->__isset($object_name) ?: $request->bind($object_name, new $class());
    
    return $request->__get($object_name);
}

/**
 * ��ȡ����������
 * @param strng $name �����
 */
function get_addon_class($name = '')
{
    
    $lower_name = strtolower($name);
    
    $class = SYS_ADDON_DIR_NAME. SYS_DS_CONS . $lower_name . SYS_DS_CONS . $name;
    
    return $class;
}

/**
 * ����
 */
function hook($tag = '', $params = [])
{
    
    \think\Hook::listen($tag, $params);
}

/**
 * �����ʾ���������ɷ��ʲ����url
 * @param string $url url
 * @param array $param ����
 */
function addons_url($url, $param = array())
{

    $parse_url  =  parse_url($url);
    $addons     =  $parse_url['scheme'];
    $controller =  $parse_url['host'];
    $action     =  $parse_url['path'];

    /* �������� */
    $params_array = array(
        'addon_name'      => $addons,
        'controller_name' => $controller,
        'action_name'     => substr($action, 1),
    );

    $params = array_merge($params_array, $param); //��Ӷ������
    
    return url('addon/execute', $params);
}

/**
 * �������ע��
 */
function addon_ioc($this_class, $name, $layer)
{
    
    !str_prefix($name, $layer) && exception('�߼���ģ�Ͳ�������ǰ׺:' . $layer);

    $class_arr = explode(SYS_DS_CONS, get_class($this_class));

    $sr_name = sr($name, $layer);

    $class_logic = SYS_ADDON_DIR_NAME . SYS_DS_CONS . $class_arr[DATA_NORMAL] . SYS_DS_CONS . $layer . SYS_DS_CONS . $sr_name;

    return get_sington_object(SYS_ADDON_DIR_NAME . '_' . $layer . '_' . $sr_name, $class_logic);
}

/**
 * �׳���Ӧ�쳣
 */
function throw_response_exception($data = [], $type = 'json')
{
    
    $response = Response::create($data, $type);

    throw new HttpResponseException($response);
}

/**
 * ��ȡ����token
 */
function get_access_token()
{

    return md5('OneBase' . API_KEY);
}

/**
 * ��ʽ���ֽڴ�С
 * @param  number $size      �ֽ���
 * @param  string $delimiter ���ֺ͵�λ�ָ���
 * @return string            ��ʽ����Ĵ���λ�Ĵ�С
 */
function format_bytes($size, $delimiter = '')
{
    
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    
    for ($i = 0; $size >= 1024 && $i < 5; $i++) {
        
        $size /= 1024;
    }
    
    return round($size, 2) . $delimiter . $units[$i];
}


// +---------------------------------------------------------------------+
// | ������غ���
// +---------------------------------------------------------------------+

/**
 * �ѷ��ص����ݼ�ת����Tree
 * @param array $list Ҫת�������ݼ�
 * @param string $pid parent����ֶ�
 * @param string $level level����ֶ�
 * @return array
 */
function list_to_tree($list, $pk='id', $pid = 'pid', $child = '_child', $root = 0)
{
    
    // ����Tree
    $tree = [];
    
    if (!is_array($list)) {
        
        return false;
    }
    
    // ����������������������
    $refer = [];

    foreach ($list as $key => $data) {

        $refer[$data[$pk]] =& $list[$key];
    }

    foreach ($list as $key => $data) {

        // �ж��Ƿ����parent
        $parentId =  $data[$pid];

        if ($root == $parentId) {

            $tree[] =& $list[$key];

        } else if (isset($refer[$parentId])){

            is_object($refer[$parentId]) && $refer[$parentId] = $refer[$parentId]->toArray();
            
            $parent =& $refer[$parentId];

            $parent[$child][] =& $list[$key];
        }
    }
    
    return $tree;
}

/**
 * �������鼰ö����������ֵ ��ʽ a:����1,b:����2
 * @return array
 */
function parse_config_attr($string)
{
    
    $array = preg_split('/[,;\r\n]+/', trim($string, ",;\r\n"));

    if (strpos($string, ':')) {

        $value = [];
        foreach ($array as $val) {
            
            list($k, $v) = explode(':', $val);
            
            $value[$k] = $v;
        }
        
    } else {
        
        $value = $array;
    }
    
    return $value;
}

/**
 * ������������
 */
function parse_config_array($name = '')
{
    
    return parse_config_attr(config($name));
}
//�����ַ�������
function parse_config_str($name = ''){
    $arr=parse_config_attr(config($name));
    return $arr[0];
}
//������
function trade_no()
{
    list($usec, $sec) = explode(" ", microtime());
    $usec = substr(str_replace('0.', '', $usec), 0, 4);
    $str = rand(10, 99);
    return date("YmdHis") . $usec . $str;
}

//����������ɣ���Ψһ
function random($length, $chars = '0123456789')
{
    $hash = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $hash .= $chars[mt_rand(0, $max)];
    }
    return $hash;
}
/**
 * ����ά�������鰴ĳ������ȡ��������µ���������
 */
function array_extract($array = [], $key = 'id')
{
    
    $count = count($array);
    
    $new_arr = [];
     
    for($i = 0; $i < $count; $i++) {
        
        if (!empty($array) && !empty($array[$i][$key])) {
            
            $new_arr[] = $array[$i][$key];
        }
    }
    
    return $new_arr;
}

/**
 * ����ĳ���ֶλ�ȡ��������
 */
function array_extract_map($array = [], $key = 'id'){
    
    
    $count = count($array);
    
    $new_arr = [];
     
    for($i = 0; $i < $count; $i++) {
        
        $new_arr[$array[$i][$key]] = $array[$i];
    }
    
    return $new_arr;
}

/**
 * ҳ�������ύ���ʽת�� 
 */
function transform_array($array)
{

    $new_array = array();
    $key_array = array();

    foreach ($array as $key=>$val) {

        $key_array[] = $key;
    }

    $key_count = count($key_array);

    foreach ($array[$key_array[0]] as $i => $val) {
        
        $temp_array = array();

        for( $j=0;$j<$key_count;$j++ ){

            $key = $key_array[$j];
            $temp_array[$key] = $array[$key][$i];
        }

        $new_array[] = $temp_array;
    }

    return $new_array;
}

/**
 * ҳ������ת���������תjson
 */
function transform_array_to_json($array)
{
    
    return json_encode(transform_array($array));
}

/**
 * ��������ת��������
 */
function relevance_arr_to_index_arr($array)
{
    
    $new_array = [];
    
    foreach ($array as $v)
    {
        
        $temp_array = [];
        
        foreach ($v as $vv)
        {
            $temp_array[] = $vv;
        }
        
        $new_array[] = $temp_array;
    }
    
    return $new_array;
}

/**
 * ����ת��Ϊ�ַ�������Ҫ���ڰѷָ����������ڶ�������
 * @param  array  $arr  Ҫ���ӵ�����
 * @param  string $glue �ָ��
 * @return string
 */
function arr2str($arr, $glue = ',')
{
    
    return implode($glue, $arr);
}

/**
 * ����ת�ַ�����ά
 * @param  array  $arr  Ҫ���ӵ�����
 * @param  string $glue �ָ��
 * @return string
 */
function arr22str($arr)
{
    
    $t ='' ;
    $temp = [];
    foreach ($arr as $v) {
        $v = join(",",$v);
        $temp[] = $v;
    }
    foreach ($temp as $v) {
        $t.=$v.",";
    }
    $t = substr($t, 0, -1);
    return $t;
}


// +---------------------------------------------------------------------+
// | �ַ�����غ���
// +---------------------------------------------------------------------+

/**
 * �ַ���ת��Ϊ���飬��Ҫ���ڰѷָ����������ڶ�������
 * @param  string $str  Ҫ�ָ���ַ���
 * @param  string $glue �ָ��
 * @return array
 */
function str2arr($str, $glue = ',')
{
    
    return explode($glue, $str);
}

/**
 * �ַ����滻
 */
function sr($str = '', $target = '', $content = '')
{
    
    return str_replace($target, $content, $str);
}

/**
 * �ַ���ǰ׺��֤
 */
function str_prefix($str, $prefix)
{
    
    return strpos($str, $prefix) === DATA_DISABLE ? true : false;
}

// +---------------------------------------------------------------------+
// | �ļ���غ���
// +---------------------------------------------------------------------+

/**
 * ��ȡĿ¼�������ļ�
 */
function file_list($path = '')
{
    
    $file = scandir($path);
    
    foreach ($file as $k => $v) {
        
        if (is_dir($path . SYS_DS_PROS . $v)) {
            
            unset($file[$k]);
        }
    }
    
    return array_values($file);
}

/**
 * ��ȡĿ¼�б�
 */
function get_dir($dir_name)
{
    
    $dir_array = [];
    
    if (false != ($handle = opendir($dir_name))) {
        
        $i = 0;
        
        while (false !== ($file = readdir($handle))) {
            
            if ($file != "." && $file != ".."&&!strpos($file,".")) {
                
                $dir_array[$i] = $file;
                
                $i++;
            }
        }
        
        closedir($handle);
    }
    
    return $dir_array;
}

/**
 * ��ȡ�ļ���Ŀ¼
 */
function get_file_root_path()
{
    
    $root_arr = explode(SYS_DS_PROS, URL_ROOT);
    
    array_pop($root_arr);
    
    $root_url = arr2str($root_arr, SYS_DS_PROS);
    
    return $root_url . SYS_DS_PROS;
}

/**
 * ��ȡͼƬurl
 */
function get_picture_url($id = 0)
{

    $fileLogic = get_sington_object('fileLogic', LogicFile::class);
    
    return $fileLogic->getPictureUrl($id);
}

/**
 * ��ȡ�ļ�url
 */
function get_file_url($id = 0)
{
    
    $fileLogic = get_sington_object('fileLogic', LogicFile::class);
    
    return $fileLogic->getFileUrl($id);
}

/**
 * ɾ�����п�Ŀ¼ 
 * @param String $path Ŀ¼·�� 
 */
function rm_empty_dir($path)
{
    
    if (!(is_dir($path) && ($handle = opendir($path))!==false)) {
        
        return false;
    }
      
    while(($file = readdir($handle))!==false)
    {

        if (!($file != '.' && $file != '..')) {
            
           continue;
        }
        
        $curfile = $path . SYS_DS_PROS . $file;// ��ǰĿ¼

        if (!is_dir($curfile)) {
            
           continue;  
        }

        rm_empty_dir($curfile);

        if (count(scandir($curfile)) == 2) {
            
            rmdir($curfile);
        }
    }

    closedir($handle); 
}


// +---------------------------------------------------------------------+
// | ʱ����غ���
// +---------------------------------------------------------------------+

/**
 * ʱ�����ʽ��
 * @param int $time
 * @return string ������ʱ����ʾ
 */
function format_time($time = null, $format='Y-m-d H:i:s')
{
    
    if (null === $time) {
        
        $time = TIME_NOW;
    }
    
    return date($format, intval($time));
}

/**
 * ��ȡָ�����ڶ���ÿһ�������
 * @param Date $startdate ��ʼ����
 * @param Date $enddate  ��������
 * @return Array
 */
function get_date_from_range($startdate, $enddate)
{
    
  $stimestamp = strtotime($startdate);
  $etimestamp = strtotime($enddate);
  
  // �������ڶ����ж�����
  $days = ($etimestamp-$stimestamp)/86400+1;
  
  // ����ÿ������
  $date = [];
  
  for($i=0; $i<$days; $i++) {
      
      $date[] = date('Y-m-d', $stimestamp+(86400*$i));
  }
  
  return $date;
}

// +---------------------------------------------------------------------+
// | ���Ժ���
// +---------------------------------------------------------------------+

/**
 * �����ݱ���ΪPHP�ļ������ڵ���
 */
function sf($arr = [], $fpath = './test.php')
{
    
    $data = "<?php\nreturn ".var_export($arr, true).";\n?>";
    
    file_put_contents($fpath, $data);
}

/**
 * dump������д
 */
function d($arr = [])
{
    dump($arr);
}

/**
 * dump��die��Ϻ�����д
 */
function dd($arr = [])
{
    dump($arr);die;
}


// +---------------------------------------------------------------------+
// | ��������
// +---------------------------------------------------------------------+

/**
 * ͨ���ഴ���߼��հ�
 */
function create_closure($object = null, $method_name = '', $parameter = [])
{
    
    $func = function() use($object, $method_name, $parameter) {
        
                return call_user_func_array([$object, $method_name], $parameter);
            };
            
    return $func;
}

/**
 * ͨ���հ����ƻ���
 */
function auto_cache($key = '', $func = '', $time = 3)
{
    
    $result = cache($key);
    
    if (empty($result)) {
        
        $result = $func();
        
        !empty($result) && cache($key, $result, $time);
    }
    
    return $result;
}

/**
 * ͨ���հ��б��������
 */
function closure_list_exe($list = [])
{
    
    Db::startTrans();
    
    try {
        
        foreach ($list as $closure) {
            
            $closure();
        }
        
        Db::commit();
        
        return true;
    } catch (\Exception $e) {
        
        Db::rollback();
        
        throw $e;
    }
}

/**
 * �Զ���װ����
 */
function trans($parameter = [], $callback = null)
{
    
    try {

        Db::startTrans();

        $backtrace = debug_backtrace(false, 2);

        array_shift($backtrace);

        $class = $backtrace[0]['class'];

        $result = (new $class())->$callback($parameter);

        Db::commit();

        return $result;

    } catch (Exception $ex) {

        Db::rollback();

        throw new Exception($ex->getMessage());
    }
}

/**
 * ���»���汾
 */
function update_cache_version($obj = null)
{
    
    $ob_auto_cache = cache('ob_auto_cache');

    is_string($obj) ? $ob_auto_cache[$obj]['version']++ : $ob_auto_cache[$obj->getTable()]['version']++;

    cache('ob_auto_cache', $ob_auto_cache);
}
