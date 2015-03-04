<?php

/**
 * Description of FrameDB
 * 数据库链接
 * @author zhangjiulong
 */
class FrameDB extends FrameObject {

    public $dsn;
    public $username;
    public $password;
    public $charset = 'utf8';

    /**
     * pdo的属性配置
     * @var type 
     */
    public $atttibutes;

    /**
     *
     * @var PDO
     */
    public $pdo;
    public $pdoClass = 'PDO';
    public $tablePrefix = '';
    private $_driverName;
    private $_transaction;
    
    /**
     * 
     * @param string $id 容器中FrameDB对应的类ID标示
     * @param boolean $throwException
     * @return FrameDB
     */
    public static function di($id='db', $throwException = true) {
        return parent::di($id, $throwException);
    }
    
    /**
     * 获取数据库链接是否已经建立
     * @return boolean 链接建立成功返回true
     */
    public function getIsActive() {
        return $this->pdo !== null;
    }
    
    public function init() {
        parent::init();
        //默认自动打开
        $this->open();
    }

    /**
     * 打开数据库链接
     * @return type
     * @throws ExceptionFrame
     */
    public function open() {
        if ($this->pdo !== null) {
            return;
        }
        if (empty($this->dsn)) {
            throw new ExceptionFrame('FrameDB::dsn cannot be empty!');
        }
        //@TODO log
        try {
            $this->pdo = $this->createPdoInstance();
            $this->initConnection();
        } catch (PDOException $e) {
            throw new ExceptionFrame($e->getMessage(), (int) $e->getCode());
        }
    }

    /**
     * 关闭数据库链接
     */
    public function close() {
        if ($this->pdo !== null) {
            //@TODO log
            $this->pdo = null;
            $this->_transaction = null;
        }
    }

    protected function createPdoInstance() {
        $pdoClass = $this->pdoClass;
        return new $pdoClass($this->dsn, $this->username, $this->password, $this->atttibutes);
    }

    protected function initConnection() {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if ($this->charset !== null && in_array($this->getDriverName(), ['mysql', 'pgsql', 'mysqli', 'cubrid'])) {
            $this->pdo->exec('SET NAMES ' . $this->pdo->quote($this->charset));
        }
    }

    public function getDriverName() {
        if ($this->_driverName === null) {
            $this->_driverName = strtolower(substr($this->dsn, 0, strpos($this->dsn, ':')));
        }
        return $this->_driverName;
    }

    public function setDriverName($driverName) {
        $this->_driverName = strtolower($driverName);
    }

    /**
     * 开启事务
     * @return \FrameTransaction
     */
    public function beginTransaction() {
        $this->open();
        if (($transaction = $this->getTransaction()) === null) {
            $transaction = $this->_transaction = new FrameTransaction(['db' => $this]);
        }
        $transaction->begin();
        return $transaction;
    }

    /**
     * 返回事务
     * @return FrameTransaction
     */
    public function getTransaction() {
        return $this->_transaction && $this->_transaction->getIsActive() ? $this->_transaction : null;
    }

    public function quoteSql($sql) {
        return preg_replace_callback(
                '/(\\{\\{(%?[\w\-\. ]+%?)\\}\\}|\\[\\[([\w\-\. ]+)\\]\\])/', function ($matches) {
            if (isset($matches[3])) {
                return $this->quoteColumnName($matches[3]);
            } else {
                return str_replace('%', $this->tablePrefix, $this->quoteTableName($matches[2]));
            }
        }, $sql
        );
    }

    public function quoteColumnName($name) {
        if (strpos($name, '(') !== false || strpos($name, '[[') !== false || strpos($name, '{{') !== false) {
            return $name;
        }
        if (($pos = strrpos($name, '.')) !== false) {
            $prefix = $this->quoteTableName(substr($name, 0, $pos)) . '.';
        } else {
            $prefix = '';
        }
        return $prefix . $this->quoteSimpleColumnName($name);
    }

    public function quoteTableName($name) {
        if (strpos($name, '(') !== false || strpos($name, '{{') !== false) {
            return $name;
        }
        if (strpos($name, '.') === false) {
            return $this->quoteSimpleTableName($name);
        }
        $parts = explode('.', $name);
        foreach ($parts as $i => $part) {
            $parts[$i] = $this->quoteSimpleTableName($part);
        }
        return implode('.', $parts);
    }

    /**
     * 引用表名
     * @param string $name
     * @return string
     */
    public function quoteSimpleTableName($name) {
        return strpos($name, '`') !== false ? $name : '`' . $name . '`';
    }

    /**
     * 引用字段名
     * @param string $name
     * @return string
     */
    public function quoteSimpleColumnName($name) {
        return strpos($name, '`') !== false || $name === '*' ? $name : '`' . $name . '`';
    }

    public function quoteValue($str) {
        if (!is_string($str)) {
            return $str;
        }
        if (($value = $this->pdo->quote($str)) !== false) {
            return $value;
        } else {
            // the driver doesn't support quote (e.g. oci)
            return "'" . addcslashes(str_replace("'", "''", $str), "\000\n\r\\\032") . "'";
        }
    }

    public function getPdoType($value) {
        static $map = array
            (
            'boolean' => PDO::PARAM_BOOL,
            'integer' => PDO::PARAM_INT,
            'string' => PDO::PARAM_STR,
            'resource' => PDO::PARAM_LOB,
            'NULL' => PDO::PARAM_NULL,
        );
        $type = gettype($value);
        return isset($map[$type]) ? $map[$type] : PDO::PARAM_STR;
    }

    public function createQuery($sql = null, $params = []) {
        $query = new FrameQuery([
            'db' => $this,
            'sql' => $sql
        ]);
        return $query->bindValues($params);
    }

}
