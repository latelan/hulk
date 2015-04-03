<?php

/**
 * Description of ICrypter
 *
 * @author zhangjiulong
 */
interface ICrypter {
    /**
     * 加密
     * @param string $string 待加密串
     * @param string $key 秘钥
     */
    public function encrpt($string,$key);
    
    /**
     * 解密
     * @param string $string 待解密串
     * @param string $key 秘钥
     */
    public function decrpt($string,$key);
}
