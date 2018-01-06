<?php

class ectools_TL_HashMap{
 
    protected $_values = array();
 
    /**
			* 构造函数
			* @param string $values  初始化数组
			*/
    public function __construct($values = array()) {
		  if (!empty($values)) {
		  	$this->_values = $values;
   		}
    }

    public function __get($name) {
		   return $this->get($name);
    }
 
    public function __set($name,$value) {
   			return $this->put($name,$value);
    }
 
    /**
			* 清除Map数据	
			*/
    public function clear() {
   			$this->_values = array();
    }
 
    /**
			* 检查Map中是否指定key
			* @param string $key  键名
			* @return boolen
			*/
    public function containsKey($key) {
		   return array_key_exists($key, $this->_values);
    }
 
    /**
			* Map是否包含指定value
			* @param string $value  键值
			* @return boolen
			*/
    public function containsValue($value) {
			  return in_array($value, $this->_values);
    }
 
 
    /**
			* 根据Key取得value
			* @param string $key  键名
			* @return mixed
			*/
    public function get($key) {
		   if ($this->containsKey($key)) {
				  return $this->_values[$key];
		   } else {
				  return null;
		   }
    }
 
    /**
			* 是否为空
			* @return boolen
			*/
    public function isEmpty() {
		   return empty($this->_values);
    }
 
    /**
			* 转换为数组
			* @return array
			*/
    public function toArray() {
   			return $this->_values;
    }
 
    /**
			* 返回key集合
			* @return array
			*/
    public function keySet() {
   			return array_keys($this->_values);
    }
 
    /**
			* 存入key-value对，当键值相同时覆盖
			* 如果存在的话返回之前的值
			* @param string $key  键值
			* @param string $value  值
			* @return mixed
			*/
    public function put($key, $value) {
		   $oldValue = $this->get($key);
		   $this->_values[$key] =&$value;
		   return $oldValue;
    }
 
 
    /**
			* 置入一个矢量到Map
			* @param array $list  数组
			*/
    public function putAll($list) {
		   if (is_array($list)) {
				  foreach ($list as $key => $value) {
						 $this->put($key, $value);
		  		}
		   }
    }
 
    /**
			* 移除指定的键值
			* 返回移除的值
			* @param mixed $key 键值
			* @return string
			*/
    public function remove($key) {
	   		$value = $this->get($key);
  	 		if (!is_null($value)) { unset($this->_values[$key]); }
   			return $value;
    }
 
    /**
			* 取得Map的长度
			* @return integer
			*/
    public function size() {
		   return count($this->_values);
    }
 
    /**
			* 返回值矢量
			* @access public
			* @return array
			*/
    public function values() {
   			return array_values($this->_values);
    }
 
}
