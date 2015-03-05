<?php

/**
 * Description of FrameQuery
 * 数据库crud操作类
 * @author zhangjiulong
 */
class FrameQuery extends FrameObject {

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
    private $_pendingParams = [];
    private $_sql;
    private $_query;

    public function __construct($config = array()) {
        parent::__construct($config);
        if ($this->db == null) {
            $this->db = FrameApp::$app->getDb();
        }
    }

    public function getSql() {
        if ($this->_sql == '' && !empty($this->_query)) {
            $this->setSql($this->buildQuery($this->_query));
        }
        return $this->_sql;
    }

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

    public function setSql($sql) {
        if ($sql !== $this->_sql) {
            $this->cancel();
            $this->_sql = $this->db->quoteSql($sql);
//            $this->params = [];
        }
        return $this;
    }

    public function execute() {
        $sql = $this->getSql();
        if ($sql == '') {
            return 0;
        }
        $this->prepare();
        
        try {
//            p($this->getRawSql(), false);
            $this->pdoStatement->execute();
            $n = $this->pdoStatement->rowCount();
            return $n;
        } catch (Exception $e) {
            throw new ExceptionFrame('Error to execute sql, ' . $e->getMessage(), (int) $e->getCode());
        }
    }

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

    public function cancel() {
        $this->pdoStatement = null;
    }

    public function reset() {
        $this->sql = null;
        $this->_pendingParams = [];
        $this->_query = null;
        $this->params = [];
        $this->cancel();
        return $this;
    }

    public function bindValues($values) {
        if (empty($values)) {
            return $this;
        }
        foreach ($values as $name => $value) {
            if (is_array($value)) {
                $this->_pendingParams[$name] = $value;
                $this->params[$name] = $value[0];
            } else {
                $type = $this->db->getPdoType($value);
                $this->_pendingParams[$name] = [$value, $type];
                $this->params[$name] = $value;
            }
        }
        return $this;
    }

    public function bindValue($name, $value, $dataType = null) {
        if ($dataType === null) {
            $dataType = $this->db->getPdoType($value);
        }
        $this->_pendingParams[$name] = [$value, $type];
        $this->params[$name] = $value;
        return $this;
    }

    public function bindPendingParams() {
        foreach ($this->_pendingParams as $name => $value) {
            $this->pdoStatement->bindParam($name, $value[0], $value[1]);
        }
        $this->_pendingParams = [];
    }

    public function buildQuery($query) {
        $sql = !empty($query['distinct']) ? 'SELECT DISTINCT' : 'SELECT';
        $sql .= ' ' . (!empty($query['select']) ? $query['select'] : '*');
        if (!empty($query['from'])) {
            $sql .= "\nFROM " . $query['from'];
        } else {
            throw new Exception('The DB query must contain the "from" portion');
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

    public function applyLimit($sql, $limit, $offset) {
        if ($limit >= 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }
        if ($offset >= 0) {
            $sql .= ' OFFSET ' . (int) $offset;
        }
        return $sql;
    }

    //插入单条数据
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

    public function getLastInsertId() {
        return $this->db->pdo->lastInsertId();
    }

    //批量插入数据
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
     * @param type $table
     * @param type $columns
     * @param type $condition
     * @param type $params
     * @return type
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

    public function delete($table, $condition = '', $params = []) {
        $sql = 'DELETE FROM ' . $this->db->quoteTableName($table);
        if (($where = $this->processConditions($condition)) != '') {
            $sql .= ' WHERE ' . $where;
        }
        return $this->setSql($sql)->bindValues($params)->execute();
    }

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

    public function getSelect() {
        return isset($this->_query['select']) ? $this->_query['select'] : '';
    }

    public function setSelect($value) {
        $this->select($value);
    }

    public function selectDistinct($columns = '*') {
        $this->_query['distinct'] = true;
        return $this->select($columns);
    }

    public function getDistinct() {
        return isset($this->_query['distinct']) ? $this->_query['distinct'] : false;
    }

    public function setDistinct($value) {
        $this->_query['distinct'] = $value;
    }

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

    public function where($conditions, $params = array()) {
        $this->_query['where'] = $this->processConditions($conditions);
        $this->addParams($params);
        return $this;
    }

    public function andWhere($conditions, $params = array()) {
        if (isset($this->_query['where']))
            $this->_query['where'] = $this->processConditions(array('AND', $this->_query['where'], $conditions));
        else
            $this->_query['where'] = $this->processConditions($conditions);

        $this->addParams($params);
        return $this;
    }

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

    public function join($table, $conditions, $params = array()) {
        return $this->joinInternal('join', $table, $conditions, $params);
    }

    public function getJoin() {
        return isset($this->_query['join']) ? $this->_query['join'] : '';
    }

    public function setJoin($value) {
        $this->_query['join'] = $value;
    }

    public function leftJoin($table, $conditions, $params = array()) {
        return $this->joinInternal('left join', $table, $conditions, $params);
    }

    public function rightJoin($table, $conditions, $params = array()) {
        return $this->joinInternal('right join', $table, $conditions, $params);
    }

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

    public function query() {
        return $this->queryInternal('', 0);
    }

    public function queryAll($fetchAssociative = true) {
        return $this->queryInternal('fetchAll', $fetchAssociative ? $this->fetchMode : PDO::FETCH_NUM);
    }

    public function queryColumn() {
        return $this->queryInternal('fetchAll', PDO::FETCH_COLUMN);
    }

    public function queryRow() {
        return $this->queryInternal('fetch');
    }

    public function queryOne() {
        $result = $this->queryInternal('fetchColumn', 0);
        if (is_resource($result) && get_resource_type($result) === 'stream') {
            return stream_get_contents($result);
        } else {
            return $result;
        }
    }

    private function queryInternal($method, $fetchMode = null) {
        //@todo
        $this->bindValues($this->params);
        $this->prepare();
        try {
//            print($this->getRawSql());
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
     * @param Query $query
     * @return int
     */
    public function count() {
        $cloneQuery = clone $this;
        $cloneQuery->limit = $cloneQuery->offset = -1;

        if (!empty($cloneQuery->group) || !empty($cloneQuery->having)) {
            $cloneQuery->order('');
            $sql = $cloneQuery->getSql();
            $sql = "SELECT COUNT(*) FROM ({$sql}) sq";
            $cloneQuery->setSql($sql);
            return $cloneQuery->queryOne();
        } else {
            $cloneQuery->select("COUNT(*)");
            $cloneQuery->order = $cloneQuery->group = $cloneQuery->having = '';
            return $cloneQuery->queryOne();
        }
    }
}
