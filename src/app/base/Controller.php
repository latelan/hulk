<?php

/**
 * Description of Controller
 * 在FrameController的基础上增加了验证的逻辑
 * @author zhangjiulong
 */
class Controller extends FrameController {
    /**
     * 返回的结果
     * @var array 
     */
    protected $_msg = [
        'errno'=>0,
        'errmsg'=>'',
        'node'=>'',
        'data'=>[
        ]
    ];
    
    public function init()
    {
        parent::init();
        $this->_msg['node'] = posix_uname()['nodename'];
    }
    
    /**
     * 设置返回的_msg里面的data
     * @param array $data
     */
    public function setData($data) {
        $this->_msg['data'] = (array)$data + $this->_msg['data'];
    }
    
    protected function beforeAction() {
        
        //执行验证请求的参数 失败则抛出异常
        $this->validate();
        return parent::beforeAction();
    }

    protected function beforeValidate() {
        return true;
    }

    protected function afterValidate() {
        
    }

    //解析验证器的参数
    protected function resolveValidateParams($params) {
        if (isset($params['on'])) {
            if (is_array($params['on'])) {
                $on = $params['on'];
            } else {
                $on = preg_split('/[\s,]+/', $params['on'], -1, PREG_SPLIT_NO_EMPTY);
            }
        } else {
            $on = [];
        }
        if (isset($params['except'])) {
            if (is_array($params['except'])) {
                $except = $params['except'];
            } else {
                $except = preg_split('/[\s,]+/', $params['except'], -1, PREG_SPLIT_NO_EMPTY);
            }
        } else {
            $except = [];
        }
        if (isset($params['when'])) {
            $when = $params['when'];
        } else {
            $when = null;
        }
        return [$on, $except, $when];
    }

    /**
     * 根据场景和条件觉得是否要验证
     * @param array $on 需要验证的场景
     * @param array $except 不需要验证的场景
     * @param callable $when 验证的条件
     * @return boolean 返回真则需要验证
     */
    protected function isNeedValidate($on, $except, $when) {
        //如果当前的actionId 不在 应用的场景下 不做验证
        if (!in_array($this->actionId, $on) && !empty($on)) {
            return false;
        }
        //如果当前的actionId 在 except场景里 则也不做验证
        if (!empty($except) && in_array($this->actionId, $except)) {
            return false;
        }
        //如果有when 并且执行when的回调为false 不做验证
        if ($when != null && !call_user_func($when)) {
            return false;
        }
        return true;
    }

    /**
     * 执行验证
     * @return boolean
     */
    public function validate() {
        if ($this->beforeValidate()) {
            foreach ($this->rules() as $rule) {
                //[0] 待验证的参数 [1] 验证器 [2]-参数
                if (isset($rule[0], $rule[1])) {
                    //获取验证方法
                    $validateMethod = $rule[1];
                    
                    //判断验证方法是否存在
                    if (!method_exists('Validator', $validateMethod)) {
                        throw new ExceptionFrame('Unknown rule name :' . $rule[1]);
                    }
                    
                    //获取待验证的参数
                    $attributes = $rule[0];
                    if (is_string($attributes)) {
                        $attributes = preg_split('/[\s,]+/', $attributes, -1, PREG_SPLIT_NO_EMPTY);
                    }
                    
                    //获取其他的参数
                    $params = array_slice($rule, 2);
                    
                    //执行验证
                    $this->doValidate($attributes, $validateMethod, $params);
                } else {
                    throw new ExceptionFrame('Invalid rule,attributes and rule name is required.');
                }
            }
            $this->afterValidate();
        }
    }

    /**
     * 执行验证
     * @param array $attributes 待验证的属性
     * @param string $validateMethod 验证方法
     * @param array $params 验证方法的参数
     * @throws ExceptionFrame
     */
    protected function doValidate($attributes, $validateMethod, $params) {
        //参数解析出验证的场景和条件
        list($on, $except, $when) = $this->resolveValidateParams($params);

        //根据条件确定是否需要验证
        $flag = $this->isNeedValidate($on, $except, $when);

        if ($flag) {
            $method = new ReflectionMethod('Validator', $validateMethod);
            $args = [];
            foreach ($method->getParameters() as $k => $param) {
                $name = $param->getName();
                if ($k == 0) {
                    //第一个是待验证的值
                    $args[0] = null;
                    continue;
                }
                if (array_key_exists($name, $params)) {
                    $args[] = $params[$name];
                    unset($params[$name]);
                } elseif ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } else {
                    $missing[] = $name;
                }
            }
            //缺失验证参数
            if (!empty($missing)) {
                throw new ExceptionFrame('Missing the param:' . implode(' | ', $missing).' of the validate method:'.$validateMethod);
            }
            $message = isset($params['message'])?$params['message']:Validator::$messages[$validateMethod];
            foreach ($attributes as $attribute) {
                //从url中获取attribute对应的值进行验证
                $validate_value = $value = $this->getRequest($attribute);
                if(isset($params['before']) && is_callable($params['before'])){
                    $validate_value = call_user_func($params['before'], $value);
                }
                $args[0] = $validate_value;
                $res = call_user_func_array(['Validator', $validateMethod], $args);
                //如果验证失败 抛出异常
                if (!$res) {
                    $val_str = ' '.implode(', ', (array)$value).' ';
                    $message = $this->getAttributeLabel($attribute).str_replace('{$value}', $val_str, $message);
//                    throw new ExceptionBiz(Errors::ERR_PARAM.': '.$message);
                    throw new ExceptionFrame('验证失败: '.$message);
                }
            }
        }
    }

    /**
     * 标签名数组
     * @return type
     */
    public function attributeLabels() {
        return array();
    }

    /**
     * 验证规则
     * @return array
     */
    public function rules() {
        return [];
    }

    public function getAttributeLabel($attribute) {
        $labels = $this->attributeLabels();
        if (isset($labels[$attribute]))
            return $labels[$attribute];
        else
            return $this->generateAttributeLabel($attribute);
    }

    public function generateAttributeLabel($name) {
        return ucwords(trim(strtolower(str_replace(array('-', '_', '.'), ' ', preg_replace('/(?<![A-Z])[A-Z]/', ' \0', $name)))));
    }

}
