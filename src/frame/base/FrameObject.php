<?php

/**
 * Description of FrameObject
 * 应用的基类，主要实现了魔术方法setter,getter,以及静态方法生成单例，静态方法创建对象
 * 例如，有个用户类
 * ~~~~~
 * class User extends FrameObject {
 *      private $_age;  //私有属性 年龄
 *      public function getAge(){
 *          return $_age - 5;
 *      }
 *      public function setAge($value){
 *          $this->_age = $value;
 *      }
 * }
 * 
 * //创建用户对象,并初始化_age的值为20
 * $user = new User(['age'=>20]);
 * //访问用户年龄
 * echo $user->age;     //相当于调用了 $user->getAge()方法,返回15
 * //设置用户年龄
 * $user->age = 25;     //相当于调用了 $user->setAge(25)
 * 
 * //除了使用关键词new，还可以使用静态方法instance来生成对象单例
 * $user = User::instance(['age'=>10])
 * 
 * ~~~~~~·
 * @author zhangjiulong
 */
class FrameObject {

    /**
     * 存储对象的单例列表
     * 
     * @var array 
     */
    private static $_instances = [];

    /**
     * 构造函数，实现对象属性的赋值
     * @param array $config
     */
    public function __construct($config = []) {
        static::configure($this, $config);
        $this->init();
    }

    /**
     * 执行对象实例化后的初始化动作
     */
    public function init() {
        
    }

    /**
     * FrameObject::instance()
     * @param array $config
     * @return $className 返回类的单例
     */
    static public function instance($config = []) {
        $className = get_called_class();
        if (!isset(self::$_instances[$className])) {
            return self::$_instances[$className] = new $className($config);
        }
        return static::configure(self::$_instances[$className], $config);
    }

    /**
     * 从容器中取对象
     * @param string $id
     * @param boolean $throwException
     * @return object
     * @throws ExceptionFrame
     */
    static public function di($id, $throwException = true) {
        if (FrameApp::$app->has($id)) {
            return FrameApp::$app->get($id);
        } elseif ($throwException) {
            throw new ExceptionFrame('Unknow object ID: ' . $id);
        } else {
            return null;
        }
    }

    /**
     * 为对象属性初始化赋值
     * @param object $object 要赋值的对象
     * @param array $properties 对象的属性，name=>value键值对数组
     * @return object 返回对象本身
     */
    public static function configure($object, $properties = []) {
        if (!empty($properties) && is_array($properties)) {
            foreach ($properties as $name => $value) {
                $object->$name = $value;
            }
        }
        return $object;
    }

    /**
     * 返回一个对象的属性值
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } else {
            return $this->__getException($name);
        }
    }
    
    /**
     * 调用魔术__get方法的属性不存在抛出的异常
     * @param string $name
     * @throws ExceptionFrame
     */
    protected function __getException($name)
    {
        throw new ExceptionFrame('Property "'.  get_class($this).'.{'.$name.'}" is not defined.');
    }

    /**
     * 设置对象的属性值
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value) {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } else {
            $this->__setException($name, $value);
        }
    }
    
    /**
     * 调用魔术__set方法的属性不存在抛出的异常
     * @param string $name
     * @param string $value
     * @throws ExceptionFrame
     */
    protected function __setException($name, $value)
    {
        throw new ExceptionFrame('Property "'.  get_class($this).'.{'.$name.'}" is not defined.');
    }
    


    /**
     * 检查属性是否设置(并且不为null)
     * @param string $name
     * @return boolean
     */
    public function __isset($name) {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        } else {
            return false;
        }
    }

    /**
     * 设置属性为空
     * @param string $name
     */
    public function __unset($name) {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter(null);
        }
    }

    /**
     * 根据对象的配置，返回一个实例化后的对象或者执行回调
     * @param mixed $definition
     * @param array $params
     * @return object
     * @throws ExceptionFrame
     */
    public static function createObject($definition, $params = []) {
        //如果是字串，说明是个类名，直接实例化
        if (is_string($definition)) {
            return new $definition($params);
        } elseif (is_array($definition) && isset($definition['class'])) {
            //如果是一个数组，并且里面有class元素，则创建class对应的对象
            $className = $definition['class'];
            unset($definition['class']);
            return new $className($definition);
        } elseif (is_callable($definition, true)) {
            //如果是一个有效的php回调，则执行回调函数
            return call_user_func($definition, $params);
        } elseif (is_array($definition)) {
            throw new ExceptionFrame('the configuration for object must contain "class" element');
//            throw new ExceptionFrame('对象的配置数组中必须包含"class"元素');
        } else {
            throw new ExceptionFrame('unknow object configuration type: ' . gettype($definition));
//            throw new ExceptionFrame('未知的对象配置类型,' . gettype($definition));
        }
    }

}
