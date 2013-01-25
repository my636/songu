<?php
/**
 * ThinkSNS 缓存管理类
 * @author  liu21st <liu21st@gmail.com>
 * @version TS3.0
 */
class Cache extends Think
{//类定义开始

    /**
     * 是否连接
     * @var string
     * @access protected
     */
    protected $connected;

    /**
     * 操作句柄
     * @var string
     * @access protected
     */
    protected $handler;

    /**
     * 缓存存储前缀
     * @var string
     * @access protected
     */
    protected $prefix = '~@';

    /**
     * 缓存连接参数
     * @var integer
     * @access protected
     */
    protected $options = array();

    /**
     * 缓存类型
     * @var integer
     * @access protected
     */
    protected    $type       ;

    /**
     * 缓存过期时间
     * @var integer
     * @access protected
     */
    protected $expire     ;
	//换成读取日志
    public static $log = array();
    
    public $debug = true;

    /**
     * 连接缓存
     * @access public
     * @param string $type 缓存类型
     * @param array $options  配置数组
     * @return object
     * @throws ThinkExecption
     */
    public function connect($type='',$options=array()) {
        if(empty($type))  $type = C('DATA_CACHE_TYPE');
        
        
        $cachePath = ADDON_PATH.'/liberary/cache/';
        $cacheClass = 'Cache'.ucwords(strtolower(trim($type)));
        $this->type = strtoupper(substr($cacheClass,5));
        tsload($cachePath.$cacheClass.'.class.php');
        
        if(class_exists($cacheClass))
            $cache = new $cacheClass($options);
        else
            throw_exception(L('_CACHE_TYPE_INVALID_').':'.$type);
        return $cache;
    }

    public function __get($name) {
        return $this->get($name);
    }

    public function __set($name,$value) {
        return $this->set($name,$value);
    }

    public function __unset($name) {
        $this->rm($name);
    }

    /**
     * 设置缓存类选项
     * @param string $name 参数名称
     * @param mixed $value 参数值
     */  
    public function setOptions($name,$value) {
        $this->options[$name]   =   $value;
    }

    /**
     * 获取缓存类选项
     * @param string $name 参数名称
     * @return mixed 参数值
     */  
    public function getOptions($name) {
        return $this->options[$name];
    }

    /**
     * 取得缓存类实例
     * @access public
     * @return mixed
     */
    static public function getInstance() {
       $param = func_get_args();
        return get_instance_of(__CLASS__,'connect',$param);
    }

    /**
     * 队列缓存 利用额外的缓存开销,保持缓存队列数,只在length>0的时候有效
     * @param string $key
     * @return boolean 是否成功
     */
    protected function queue($key) {
        $value   =  S('think_queue');
        if(!$value) {
            $value   =  array();
        }
        // 进列
        array_push($value,$key);
        if(count($value) > $this->options['length']) {
            // 出列
            $key =  array_shift($value);
            // 删除缓存
            $this->rm($key);
        }
        return S('think_queue',$value);
    }

    /**
     * 记录缓存执行次数
     * @param string $type 缓存操作类型cache_read读/cache_write写
     * @param string $nums 具体次数
     * @param string $name 缓存名称
     * @return intval 返回读/写次数
     */
	public function N($type,$nums=1,$name){
    	$f = $type == 'cache_read' ? 'Q' : 'W';
    	$this-> $f(1,$name);
    }

    /**
     * 记录读取缓存次数
     * @param string $times
     * @param string $name 
     * @return intval 返回读/写次数
     */
    public function Q($times='',$name) {
    	
        static $_times = 0;
        
        if($this->debug){
        	self::$log['Q'] = $_times +1;
        	self::$log['Qkey'][] = $name;
        }
        
        if(empty($times))
            return $_times;
        else
            $_times++;
    }

    /**
     * 记录写入缓存次数
     * @param string $times
     * @param string $name 
     * @return intval 返回读/写次数
     */
    public  function W($times='',$name) {
        static $_times = 0;
        if($this->debug){
        	self::$log['W'] = $_times +1;
        	self::$log['Wkey'][] = $name;
        }
        if(empty($times))
            return $_times;
        else
            $_times++;
    }
}//类定义结束
?>