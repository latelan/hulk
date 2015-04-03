<?php

/**
 * Description of CryptUtil
 * 加解密组件
 * @author zhangjiulong
 */
class CryptUtil extends FrameObject {

    /**
     * 加解密秘钥
     * @var string 
     */
    public $key = 'rds';

    /**
     * 加解密实例类,必须是ICrypter的子类
     * @var ICrypter
     */
    private $_crypter;

    /**
     * 返回加解密组件单例
     * @param string $id
     * @param boolean $throwException
     * @return CryptUtil
     */
    public static function di($id = 'crypt', $throwException = true) {
        return parent::di($id, $throwException);
    }

    /**
     * 设置加解密类
     * @param callable $crypter
     */
    public function setCrypter($crypter) {
        $this->_crypter = self::createObject($crypter);
    }

    /**
     * 获取加解密类
     * @return ICrypter
     */
    public function getCrypter() {
        return $this->_crypter;
    }

    //加密
    public function encrypt($string) {
        return $this->getCrypter()->encrpt($string, $this->key);
    }

    //解密
    public function decrypt($string) {
        return $this->getCrypter()->decrpt($string, $this->key);
    }

}
