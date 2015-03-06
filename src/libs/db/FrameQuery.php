<?php

/**
 * Description of FrameQuery
 * 数据库crud操作类
 * for example
 * ~~~~~~~
 * 获取query对象
 * $db = new FrameDB(['dsn'=>'mysql:dbname=testdb;host=127.0.0.1','username'=>'xxx','password'=>'xxxx']);   //db连接对象应采用更合理的单例
 * $query = new FrameQuery(['db'=>$db]);
 * 
 * 单条语句插入：
 * $res = $query->insert('table',array(
 *      'id'=>'15',
 *      'name'=>'zhangjiulong'
 * ));
 * return : 影响的行数
 * 如果要获取最新插入的自增id，可使用 $query->getLastInsertId() 获取
 * 
 * 多条语句插入：
 * $res = $query->batchInsert('table',array('id','name'),array(
 *      array('16','lisi'),
 *      array('17','wangwu'),
 * ));
 * return: 影响的行数
 * 
 * 修改：
 * $res = $query->update('table',array(
 *      'name'=>'liuliu'
 * ),'id=:id',[':id'=>17]);
 * return: 受影响的行数
 * 
 * 删除：
 * $res = $query->delete('table','id=:id',[':id'=>17]);
 * return: 返回受影响的行数
 * 
 * 查询所有集合(采用单个查询条件)
 * $res = $query->select('*')->from('table')->where('age>:age',[':age'=>30])->queryAll();
 * return: 二维关联数组
 * [
 *      ['id'=>1,'name'=>'zs','age'=>20],
 *      ['id'=>2,'name'=>'li','age'=>35],
 *      ....
 * ]
 * 
 * 查询一条记录（采用多个查询条件）
 * $res = $query->select('id,name,age')->from('table')->where('age>:age',[':age'=>30])->andWhere('name=:name',[':name'=>'zs'])->queryRow()
 * return: 一维关联数组
 * ['id'=>1,'name'=>'zs','age'=>20]
 * 
 * 查询一个值
 * $res = $query->select('count(1)')->from('table')->queryOne();
 * return: count(1)对应的值 
 * 10
 * 
 * 查询某个字段的集合
 * $res = $query->select('id')->from('table')->where('id>0')->getColumn();
 * return: 一维索引数组
 * [1,2,3,4,5,10]
 * 
 * 内联查询
 * $res = $query->select('a.*,b.*')
 *              ->from('table_a as a')
 *              ->join('table_b as b','a.aid=b.bid')
 *              ->where('a.aid>:aid',[':aid'=>50])
 *              ->queryAll();
 * 
 * 左联查询
 * $res = $query->select('a.*,b.*')
 *              ->from('table_a as a')
 *              ->leftJoin('table_b as b','a.aid=b.bid')
 *              ->where('a.aid>:aid',[':aid'=>50])
 *              ->queryAll();
 * 
 * 分组使用
 * $res = $query->select('min(id)')->from('table')->group('age')->queryAll();
 * 
 * 排序分页(页大小5,当前页是2，取id大于10的集合，按id倒叙排列)
 * $page = 2;   $pagesize = 5;
 * 1. 组装where条件
 * $query->select('id,name,age')->from('table')->where('id>:id',[':id'=>10])->order('id desc');
 * 2. 获取总数
 * $total = $query->count();
 * 3. 根据页大小设置偏移量
 * $query->limit($pagesize,$pagesize*($page-1));
 * 4. 执行查询
 * $res = $query->queryAll()
 * (表示取id大于10的集合，按id倒叙排列，取10条，跳过5条)
 * 
 * where条件的使用
 * in | not in 查询场景
 * where(['in',$column,$values]) //参数为一个数组，第一个值为in|IN|not in|NOT IN,第二个值$column为in查询的字段名，第三个值$values是in条件的数组
 * like查询场景
 * where(['like',$column,'%'.$value.'%']) //参数为一个数组，第一个值为like|LIKE,第二个值$column为like查询的字段名，第三个值$value是like条件
 * > | >= | < | <= | = 比较的条件
 * where('id>:id',[':id'=>10]); //比较条件与sql写法一致
 * 
 * ~~~~~~~~
 * @author zhangjiulong
 */
class FrameQuery extends FrameObject{

    /**
     *
     * @var FrameDB
     */
    public $db;

    /**
     *
     * @var PDOStatement
     */
    public $pdoStatement;

    /**
     * PDO的取值模式，默认为取关联数组
     * @var int
     */
    public $fetchMode = PDO::FETCH_ASSOC;

    /**
     * 对预处理语句绑定的参数
     * @var array 
     */
    public $params = [];

    /**
     * 实际绑定的参数，在bindValues()中使用
     * @var type 
     */
    private $_pendingParams = [];

    /**
     * sql语句
     * @var string 
     */
    private $_sql;

    /**
     * SQL语句的组装数组
     * @var array
     */
    private $_query;

    public function __construct($config = array()) {
        parent::__construct($config);
        if ($this->db == null) {
            $this->db = FrameApp::$app->getDb();
        }
    }
    
    /**
     * 返回当前的sql语句（未绑定参数）
     * @return string
     */
    public function getSql() {
        if ($this->_sql == '' && !empty($this->_query)) {
            $this->setSql($this->buildQuery($this->_query));
        }
        return $this->_sql;
    }

    /**
     * 实际执行的sql语句(参数绑定后的)
     * @return string
     */
    public function getRawSql() {
        if (empty($this->params)) {
            return $this->getSql();
        } else {
            $params = [];
            foreach ($this->params as $name => $value) {
                if (is_string($value)) {
                    $params[$name] = $this->db->quoteValue($value);
                } elseif ($value === null) {
                    $params[$name] = 'NULL';
                } else {
                    $params[$name] = $value;
                }
            }
            if (isset($params[1])) {
                $sql = '';
                foreach (explode('?', $this->getSql()) as $i => $part) {
                    $sql .= (isset($params[$i]) ? $params[$i] : '') . $part;
                }
                return $sql;
            } else {
                return strtr($this->getSql(), $params);
            }
        }
    }

    /**
     * 设置sql语句
     * @param string $sql
     * @return \FrameQuery
     */
    public function setSql($sql) {
        if ($sql !== $this->_sql) {
            $this->cancel();
            $this->_sql = $this->db->quoteSql($sql);
        }
        return $this;
    }

    /**
     * 执行sql
     * @return int 返回影响的行数
     * @throws ExceptionFrame
     */
    public function execute() {
        $sql = $this->getSql();
        if ($sql == '') {
            return 0;
        }
        $this->prepare();

        try {
            //@TODO 此处可记录SQL日志 log($this->getRawSql())
            $this->pdoStatement->execute();
            $n = $this->pdoStatement->rowCount();
            return $n;
        } catch (Exception $e) {
            throw new ExceptionFrame('Error to execute sql, ' . $e->getMessage(), (int) $e->getCode());
        }
    }

    /**
     * SQL语句的预处理
     * @return null
     * @throws ExceptionFrame
     */
    public function prepare() {
        try {
            if ($this->pdoStatement) {
                $this->bindPendingParams();
                return;
            }
            $sql = $this->getSql();
            /**
             * 连接数据库
             */
            $this->db->open();
            /**
             * 预处理
             */
            $this->pdoStatement = $this->db->pdo->prepare($sql);
            /**
             * 绑定参数
             */
            $this->bindPendingParams();
        } catch (Exception $e) {
            throw new ExceptionFrame('Fail to prepare SQL: ' . $sql . ',' . $e->getMessage(), (int) $e->getCode());
        }
    }

    /**
     * 取消sql预处理和参数绑定
     */
    public function cancel() {
        $this->pdoStatement = null;
    }

    /**
     * 重置Query对象
     * @return \FrameQuery
     */
    public function reset() {
        $this->_sql = null;
        $this->_pendingParams = [];
        $this->_query = null;
        $this->params = [];
        $this->cancel();
        return $this;
    }

    /**
     * 绑定值
     * @param array $values
     * @return \FrameQuery
     */
    public function bindValues($values) {
        if (empty($values)) {
            return $this;
        }
        foreach ($values as $name => $value) {
            if (is_array($value)) {
                $this->_pendingParams[$name] = $value;
                $this->params[$name] = $value;
            } else {
                $type = $this->db->getPdoType($value);
                $this->_pendingParams[$name] = [$value, $type];
                $this->params[$name] = $value;
            }
        }
        return $this;
    }

    /**
     * 绑定单个值
     * @param string $name
     * @param mixed $value
     * @param int $dataType
     * @return \FrameQuery
     */
    public function bindValue($name, $value, $dataType = null) {
        if ($dataType === null) {
            $dataType = $this->db->getPdoType($value);
        }
        $this->_pendingParams[$name] = [$value, $dataType];
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * 执行PDOstatement的参数绑定
     */
    public function bindPendingParams() {
        foreach ($this->_pendingParams as $name => $value) {
            $this->pdoStatement->bindParam($name, $value[0], $value[1]);
        }
        $this->_pendingParams = [];
    }

    /**
     * 生成sql语句通过$query数组
     * @param array $query
     * @return string
     * @throws ExceptionFrame
     */
    public function buildQuery($query) {
        $sql = !empty($query['distinct']) ? 'SELECT DISTINCT' : 'SELECT';
        $sql .= ' ' . (!empty($query['select']) ? $query['select'] : '*');
        if (!empty($query['from'])) {
            $sql .= "\nFROM " . $query['from'];
        } else {
            throw new ExceptionFrame('The DB query must contain the "from" portion');
        }
        if (!empty($query['join'])) {
            $sql .= "\n" . (is_array($query['join']) ? implode("\n", $query['join']) : $query['join']);
        }
        if (!empty($query['where'])) {
            $sql .= "\nWHERE " . $query['where'];
        }
        if (!empty($query['group'])) {
            $sql .= "\nGROUP BY " . $query['group'];
        }
        if (!empty($query['having'])) {
            $sql .= "\nHAVING " . $query['having'];
        }
        if (!empty($query['union'])) {
            $sql .= "\nUNION (\n" . (is_array($query['union']) ? implode("\n) UNION (\n", $query['union']) : $query['union']) . ')';
        }
        if (!empty($query['order'])) {
            $sql .= "\nORDER BY " . $query['order'];
        }
        $limit = isset($query['limit']) ? (int) $query['limit'] : -1;
        $offset = isset($query['limit']) ? (int) $query['offset'] : -1;
        if ($limit > 0 || $offset > 0) {
            $sql = $this->applyLimit($sql, $limit, $offset);
        }
        return $sql;
    }

    /**
     * 处理sql的偏移量和limit语句
     * @param string $sql
     * @param int $limit
     * @param int $offset
     * @return string
     */
    public function applyLimit($sql, $limit, $offset) {
        if ($limit >= 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }
        if ($offset >= 0) {
            $sql .= ' OFFSET ' . (int) $offset;
        }
        return $sql;
    }

    /**
     * 插入单条数据
     * @param string $table 表名
     * @param array $columns [字段=>值]
     * @return int 影响的行数
     */
    public function insert($table, $columns) {
        $params = [];
        $names = [];
        $placeholders = [];
        foreach ($columns as $name => $value) {
            $names[] = $this->db->quoteColumnName($name);
            if ($value instanceof FrameDbExpression) {
                $placeholders[] = $value->expression;
                foreach ($value->params as $n => $v) {
                    $params[$n] = $v;
                }
            } else {
                $placeholders[] = ':' . $name;
                $params[':' . $name] = $value;
            }
        }
        $sql = 'INSERT INTO ' . $this->db->quoteTableName($table)
                . ' (' . implode(', ', $names) . ') VALUES ('
                . implode(', ', $placeholders) . ')';
        
        return $this->setSql($sql)->bindValues($params)->execute();
    }

    /**
     * 返回最新插入的一个自增id
     * @return string
     */
    public function getLastInsertId() {
        return $this->db->pdo->lastInsertId();
    }

    /**
     * 批量插入数据
     * @param string $table 表名
     * @param array $columns 字段列表[column1,column2...]
     * @param array $rows 多行值 [[column1=>value1,column2=>value2],[....]]
     * @return 影响的行数
     */
    public function batchInsert($table, $columns, $rows) {
        $values = [];
        foreach ($rows as $row) {
            $vs = array();
            foreach ($row as $i => $value) {
                if (is_string($value)) {
                    $this->addParams([$value]);
                    $value = '?';
                } elseif ($value === false) {
                    $value = 0;
                } elseif ($value === null) {
                    $value = 'NULL';
                }
                $vs[] = $value;
            }
            $values[] = '(' . implode(', ', $vs) . ')';
        }

        foreach ($columns as $i => $name) {
            $columns[$i] = $this->db->quoteColumnName($name);
        }

        $sql = 'INSERT INTO ' . $this->db->quoteTableName($table) .
                '(' . implode(', ', $columns) . ') VALUES ' . implode(', ', $values);

        return $this->setSql($sql)->bindValues($this->params)->execute();
    }

    /**
     * 修改表记录
     * @param string $table 表名
     * @param array $columns [字段=>值]
     * @param string|array $condition 条件表达式
     * @param array $params 参数
     * @return int 影响的行数
     */
    public function update($table, $columns, $condition = '', $params = []) {
        $lines = array();
        foreach ($columns as $name => $value) {
            if ($value instanceof FrameDbExpression) {
                $lines[] = $this->db->quoteColumnName($name) . '=' . $value->expression;
                foreach ($value->params as $n => $v) {
                    $params[$n] = $v;
                }
            } else {
                $lines[] = $this->db->quoteColumnName($name) . '=:' . $name;
                $params[':' . $name] = $value;
            }
        }
        $sql = 'UPDATE ' . $this->db->quoteTableName($table) . ' SET ' . implode(', ', $lines);
        if (($where = $this->processConditions($condition)) != '') {
            $sql .= ' WHERE ' . $where;
        }
        return $this->setSql($sql)->bindValues($params)->execute();
    }

    /**
     * 删除表记录
     * @param string $table 表名
     * @param array|string $condition 条件表达式
     * @param array $params 待绑定的参数
     * @return int 影响的行数
     */
    public function delete($table, $condition = '', $params = []) {
        $sql = 'DELETE FROM ' . $this->db->quoteTableName($table);
        if (($where = $this->processConditions($condition)) != '') {
            $sql .= ' WHERE ' . $where;
        }
        return $this->setSql($sql)->bindValues($params)->execute();
    }

    /**
     * 相当于sql中的select语句
     * @param string|array $columns 字段列表
     * @param string $option
     * @return \FrameQuery
     */
    public function select($columns = '*', $option = '') {
        if (is_string($columns) && strpos($columns, '(') !== false) {
            $this->_query['select'] = $columns;
        } else {
            if (!is_array($columns))
                $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);

            foreach ($columns as $i => $column) {
                if (is_object($column))
                    $columns[$i] = (string) $column;
                elseif (strpos($column, '(') === false) {
                    if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)(.*)$/', $column, $matches))
                        $columns[$i] = $this->db->quoteColumnName($matches[1]) . ' AS ' . $this->db->quoteColumnName($matches[2]);
                    else
                        $columns[$i] = $this->db->quoteColumnName($column);
                }
            }
            $this->_query['select'] = implode(', ', $columns);
        }
        if ($option != '')
            $this->_query['select'] = $option . ' ' . $this->_query['select'];
        return $this;
    }

    /**
     * 获取select语句
     * @return type
     */
    public function getSelect() {
        return isset($this->_query['select']) ? $this->_query['select'] : '';
    }

    /**
     * 设置select语句
     * @param string|array $value
     */
    public function setSelect($value) {
        $this->select($value);
    }

    /**
     * 设置select distinct的情况
     * @param string $columns
     * @return type
     */
    public function selectDistinct($columns = '*') {
        $this->_query['distinct'] = true;
        return $this->select($columns);
    }

    /**
     * 返回是否有distinct
     * @return string
     */
    public function getDistinct() {
        return isset($this->_query['distinct']) ? $this->_query['distinct'] : false;
    }

    /**
     * 设置是否有disctict
     * @param boolean $value
     */
    public function setDistinct($value) {
        $this->_query['distinct'] = $value;
    }

    /**
     * 相当于sql中的from语句
     * @param string|array $tables 表名
     * @return \FrameQuery
     */
    public function from($tables) {
        if (is_string($tables) && strpos($tables, '(') !== false)
            $this->_query['from'] = $tables;
        else {
            if (!is_array($tables))
                $tables = preg_split('/\s*,\s*/', trim($tables), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($tables as $i => $table) {
                if (strpos($table, '(') === false) {
                    if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)(.*)$/', $table, $matches))  // with alias
                        $tables[$i] = $this->db->quoteTableName($matches[1]) . ' ' . $this->db->quoteTableName($matches[2]);
                    else
                        $tables[$i] = $this->db->quoteTableName($table);
                }
            }
            $this->_query['from'] = implode(', ', $tables);
        }
        return $this;
    }

    public function getFrom() {
        return isset($this->_query['from']) ? $this->_query['from'] : '';
    }

    public function setFrom($value) {
        $this->from($value);
    }

    /**
     * 添加待绑定的参数
     * @param array $params
     * @return \FrameQuery
     */
    public function addParams($params) {
        if (!empty($params)) {
            if (empty($this->params)) {
                //@TODO ?参数的绑定
                if (isset($params[0])) {
                    foreach ($params as $i => $value) {
                        $this->params[$i + 1] = $value;
                    }
                } else {
                    $this->params = $params;
                }
            } else {
                foreach ($params as $name => $value) {
                    if (is_integer($name)) {
                        $this->params[] = $value;
                    } else {
                        $this->params[$name] = $value;
                    }
                }
            }
        }
        return $this;
    }

    /**
     * 设置where条件，相当于sql中的where
     * @param string|array $conditions
     * @param array $params
     * @return \FrameQuery
     */
    public function where($conditions, $params = array()) {
        $this->_query['where'] = $this->processConditions($conditions);
        $this->addParams($params);
        return $this;
    }

    /**
     * 设置where条件，相当于sql中的and where
     * @param string|array $conditions
     * @param array $params
     * @return \FrameQuery
     */
    public function andWhere($conditions, $params = array()) {
        if (isset($this->_query['where']))
            $this->_query['where'] = $this->processConditions(array('AND', $this->_query['where'], $conditions));
        else
            $this->_query['where'] = $this->processConditions($conditions);

        $this->addParams($params);
        return $this;
    }

    /**
     * 设置where条件，相当于sql中的or where
     * @param string|array $conditions
     * @param array $params
     * @return \FrameQuery
     */
    public function orWhere($conditions, $params = array()) {
        if (isset($this->_query['where']))
            $this->_query['where'] = $this->processConditions(array('OR', $this->_query['where'], $conditions));
        else
            $this->_query['where'] = $this->processConditions($conditions);

        $this->addParams($params);
        return $this;
    }

    public function getWhere() {
        return isset($this->_query['where']) ? $this->_query['where'] : '';
    }

    public function setWhere($value) {
        $this->where($value);
    }

    /**
     * 内联 join table
     * @param string $table 表名
     * @param string $conditions 连接条件
     * @param array $params
     * @return \FrameQuery
     */
    public function join($table, $conditions, $params = array()) {
        return $this->joinInternal('join', $table, $conditions, $params);
    }

    public function getJoin() {
        return isset($this->_query['join']) ? $this->_query['join'] : '';
    }

    public function setJoin($value) {
        $this->_query['join'] = $value;
    }

    /**
     * 左连接 left join table
     * @param string $table 表名
     * @param string $conditions 连接条件
     * @param array $params
     * @return \FrameQuery
     */
    public function leftJoin($table, $conditions, $params = array()) {
        return $this->joinInternal('left join', $table, $conditions, $params);
    }

    /**
     * 右连接 right join table
     * @param string $table 表名
     * @param string $conditions 连接条件
     * @param array $params
     * @return \FrameQuery
     */
    public function rightJoin($table, $conditions, $params = array()) {
        return $this->joinInternal('right join', $table, $conditions, $params);
    }

    /**
     * 分组 group by column
     * @param array|string $columns
     * @return \FrameQuery
     */
    public function group($columns) {
        if (is_string($columns) && strpos($columns, '(') !== false)
            $this->_query['group'] = $columns;
        else {
            if (!is_array($columns))
                $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($columns as $i => $column) {
                if (is_object($column))
                    $columns[$i] = (string) $column;
                elseif (strpos($column, '(') === false)
                    $columns[$i] = $this->db->quoteColumnName($column);
            }
            $this->_query['group'] = implode(', ', $columns);
        }
        return $this;
    }

    public function getGroup() {
        return isset($this->_query['group']) ? $this->_query['group'] : '';
    }

    public function setGroup($value) {
        $this->group($value);
    }

    /**
     * having语句
     * @param string|array $conditions
     * @param array $params
     * @return \FrameQuery
     */
    public function having($conditions, $params = array()) {
        $this->_query['having'] = $this->processConditions($conditions);
        $this->addParams($params);
        return $this;
    }

    public function getHaving() {
        return isset($this->_query['having']) ? $this->_query['having'] : '';
    }

    public function setHaving($value) {
        $this->having($value);
    }

    /**
     * order by 语句
     * @param string|array $columns
     * @return \FrameQuery
     */
    public function order($columns) {
        if (is_string($columns) && strpos($columns, '(') !== false)
            $this->_query['order'] = $columns;
        else {
            if (!is_array($columns))
                $columns = preg_split('/\s*,\s*/', trim($columns), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($columns as $i => $column) {
                if (is_object($column))
                    $columns[$i] = (string) $column;
                elseif (strpos($column, '(') === false) {
                    if (preg_match('/^(.*?)\s+(asc|desc)$/i', $column, $matches))
                        $columns[$i] = $this->db->quoteColumnName($matches[1]) . ' ' . strtoupper($matches[2]);
                    else
                        $columns[$i] = $this->db->quoteColumnName($column);
                }
            }
            $this->_query['order'] = implode(', ', $columns);
        }
        return $this;
    }

    public function getOrder() {
        return isset($this->_query['order']) ? $this->_query['order'] : '';
    }

    public function setOrder($value) {
        $this->order($value);
    }

    /**
     * limit语句
     * @param int $limit 限制的条件
     * @param int $offset 偏移量
     * @return \FrameQuery
     */
    public function limit($limit, $offset = null) {
        $this->_query['limit'] = (int) $limit;
        if ($offset !== null)
            $this->offset($offset);
        return $this;
    }

    public function getLimit() {
        return isset($this->_query['limit']) ? $this->_query['limit'] : -1;
    }

    public function setLimit($value) {
        $this->limit($value);
    }

    /**
     * offset语句
     * @param int $offset 偏移量
     * @return \FrameQuery
     */
    public function offset($offset) {
        $this->_query['offset'] = (int) $offset;
        return $this;
    }

    public function getOffset() {
        return isset($this->_query['offset']) ? $this->_query['offset'] : -1;
    }

    public function setOffset($value) {
        $this->offset($value);
    }

    /**
     * union 语句
     * @param string $sql
     * @return \FrameQuery
     */
    public function union($sql) {
        if (isset($this->_query['union']) && is_string($this->_query['union']))
            $this->_query['union'] = array($this->_query['union']);

        $this->_query['union'][] = $sql;

        return $this;
    }

    public function getUnion() {
        return isset($this->_query['union']) ? $this->_query['union'] : '';
    }

    public function setUnion($value) {
        $this->_query['union'] = $value;
    }

    /**
     * 组装条件
     * @param string|array $conditions
     * @return string
     * @throws ExceptionFrame
     */
    protected function processConditions($conditions) {
        if (!is_array($conditions)) {
            return $conditions;
        } elseif ($conditions === array()) {
            return '';
        }
        $n = count($conditions);
        $operator = strtoupper($conditions[0]);
        if ($operator === 'OR' || $operator === 'AND') {
            $parts = array();
            for ($i = 1; $i < $n; ++$i) {
                $condition = $this->processConditions($conditions[$i]);
                if ($condition !== '') {
                    $parts[] = '(' . $condition . ')';
                }
            }
            return $parts === array() ? '' : implode(' ' . $operator . ' ', $parts);
        }

        if (!isset($conditions[1], $conditions[2])) {
            return '';
        }
        $column = $conditions[1];
        if (strpos($column, '(') === false) {
            $column = $this->db->quoteColumnName($column);
        }
        $values = $conditions[2];
        if (!is_array($values)) {
            $values = array($values);
        }
        if (in_array($operator, array('IN', 'NOT IN'))) {
            if ($values === array()) {
                return $operator === 'IN' ? '0=1' : '';
            }
            foreach ($values as $i => $value) {
                if (is_string($value)) {
                    $values[$i] = $this->db->quoteValue($value);
                } else {
                    $values[$i] = (string) $value;
                }
            }
            return $column . ' ' . $operator . ' (' . implode(', ', $values) . ')';
        }

        if (in_array($operator, array('LIKE', 'NOT LIKE', 'OR LIKE', 'OR NOT LIKE'))) {
            if ($values === array()) {
                return $operator === 'LIKE' || $operator === 'OR LIKE' ? '0=1' : '';
            }
            if ($operator === 'LIKE' || $operator === 'NOT LIKE') {
                $andor = ' AND ';
            } else {
                $andor = ' OR ';
                $operator = $operator === 'OR LIKE' ? 'LIKE' : 'NOT LIKE';
            }
            $expressions = [];
            foreach ($values as $value) {
                $expressions[] = $column . ' ' . $operator . ' ' . $this->db->quoteValue($value);
            }
            return implode($andor, $expressions);
        }
        throw new ExceptionFrame('unknow operator: ' . $operator);
    }

    /**
     * 组装连接语句
     * @param string $type 连接类型
     * @param string $table 表名
     * @param string|array $conditions 连接条件
     * @param array $params 参数绑定
     * @return \FrameQuery
     */
    private function joinInternal($type, $table, $conditions = '', $params = array()) {
        if (strpos($table, '(') === false) {
            if (preg_match('/^(.*?)(?i:\s+as\s+|\s+)(.*)$/', $table, $matches))  // with alias
                $table = $this->db->quoteTableName($matches[1]) . ' ' . $this->db->quoteTableName($matches[2]);
            else
                $table = $this->db->quoteTableName($table);
        }

        $conditions = $this->processConditions($conditions);
        if ($conditions != '')
            $conditions = ' ON ' . $conditions;

        if (isset($this->_query['join']) && is_string($this->_query['join']))
            $this->_query['join'] = array($this->_query['join']);

        $this->_query['join'][] = strtoupper($type) . ' ' . $table . $conditions;

        $this->addParams($params);
        return $this;
    }

    /**
     * 返回PDOstatement对象
     * @return PDOStatement
     */
    public function query() {
        return $this->queryInternal('', 0);
    }

    /**
     * 返回多行数据
     * @return array
     */
    public function queryAll() {
        return $this->queryInternal('fetchAll');
    }

    /**
     * 返回一列数据
     * @return array
     */
    public function queryColumn() {
        return $this->queryInternal('fetchAll', PDO::FETCH_COLUMN);
    }

    /**
     * 返回一行数据
     * @return array
     */
    public function queryRow() {
        return $this->queryInternal('fetch');
    }

    /**
     * 返回第一行第一列
     * @return string
     */
    public function queryOne() {
        $result = $this->queryInternal('fetchColumn', 0);
        if (is_resource($result) && get_resource_type($result) === 'stream') {
            return stream_get_contents($result);
        } else {
            return $result;
        }
    }

    /**
     * 执行查询
     * @param string $method 查询类型
     * @param 返回的数据结构 $fetchMode
     * @return mixed
     * @throws ExceptionFrame
     */
    private function queryInternal($method, $fetchMode = null) {
        //绑定参数
        $this->bindValues($this->params);
        /**
         * 预处理
         */
        $this->prepare();
        try {
            //@TODO 此处记录查询语句 log($this->getRawSql())
            $this->pdoStatement->execute();
            if ($method == '') {
                return $this->pdoStatement;
            } else {
                if ($fetchMode === null) {
                    $fetchMode = $this->fetchMode;
                }
                $result = call_user_func_array([$this->pdoStatement, $method], (array) $fetchMode);
                $this->pdoStatement->closeCursor();
                return $result;
            }
        } catch (Exception $e) {
            throw new ExceptionFrame('Query fail:' . $e->getMessage(), (int) $e->getCode());
        }
    }

    /**
     * 根据query对象获取总条数
     * @return int
     */
    public function count() {
        $cloneQuery = clone $this;
        $cloneQuery->limit(-1, -1);
        $group = $cloneQuery->getGroup();
        $having = $cloneQuery->getHaving();
        if (!empty($group) || !empty($having)) {
            $cloneQuery->order('');
            $sql = $cloneQuery->getSql();
            $sql = "SELECT COUNT(*) FROM ({$sql}) sq";
            $cloneQuery->setSql($sql);
            return $cloneQuery->queryOne();
        } else {
            $cloneQuery->select("COUNT(*)");
            $cloneQuery->order(''); ;
            $cloneQuery->group('');
            $cloneQuery->having('');
            return $cloneQuery->queryOne();
        }
    }

}
