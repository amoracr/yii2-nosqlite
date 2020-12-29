<?php

/**
 * @copyright Copyright (c) 2020 Alonso Mora
 * @license   https://github.com/amoracr/yii2-backup/blob/master/LICENSE.md
 * @link      https://github.com/amoracr/yii2-backup#readme
 * @author    Alonso Mora <adelfunscr@gmail.com>
 */

namespace amoracr\nosqlite;

/**
 * Description of Shelf
 *
 * @author alonso
 */
class Shelf
{

    public $database;
    public $name;
    protected $selectFields = [];
    protected $tables = [];
    protected $conditions = [];
    protected $groupBy = [];
    protected $distinct = false;

    public function __construct($name, &$database)
    {
        $this->name = $name;
        $this->database = $database;
    }

    public function insert(&$document)
    {
        $query = sprintf("INSERT INTO `%s` (`document`) VALUES (?)", $this->name);
        $stm = $this->database->prepare($query);
        $stm->bindValue(1, json_encode($document), SQLITE3_TEXT);
        $stm->execute();
    }

    public function countDocuments()
    {
        $query = sprintf("SELECT COUNT(*) AS count FROM `%s`", $this->name);
        $result = $this->database->query($query);
        $numRows = $result->fetchColumn();
        return (int) $numRows;
    }

    public function select($fields = [])
    {
        foreach ($fields as $key => $field) {
            if (is_string($key)) {
                $alias = $key;
            } else {
                $tmp = sprintf('$.%s', $field);
                $path = explode('.', $tmp);
                $alias = end($path);
            }
            $column = sprintf("json_extract(document, '$.%s') AS %s ", $field, $alias);
            $this->selectFields[$alias] = $column;
        }

        return $this;
    }

    public function selectDistinct($fields = [])
    {
        $this->distinct = true;
        $this->select($fields);
        return $this;
    }

    public function countNested($nestedFields = [])
    {
        foreach ($nestedFields as $key => $field) {
            if (is_string($key)) {
                $alias = $key;
            } else {
                $tmp = sprintf('$.%s', $field);
                $path = explode('.', $tmp);
                $alias = 'count_';
                $alias .= end($path);
            }
            $column = sprintf("json_array_length(document, '$.%s') AS %s ", $field, $alias);
            $this->selectFields[$alias] = $column;
        }

        return $this;
    }

    public function countGroupBy($fields = [])
    {
        foreach ($fields as $key => $field) {
            if (is_string($key)) {
                $alias = $key;
            } else {
                $alias = str_replace('.', '_', $field);
            }
            $countAlias = 'count_';
            $countAlias .= $alias;
            $column = sprintf("json_extract(document, '$.%s') AS %s", $field, $alias);
            $this->selectFields[$alias] = $column;
            $this->selectFields[$countAlias] = "COUNT(*) AS $countAlias";
            if (!in_array($alias, $this->groupBy)) {
                array_push($this->groupBy, $alias);
            }
        }
        return $this;
    }

    public function whereEquals($field, $search)
    {
        $conditions = sprintf("json_extract(document, '$.%s') = '%s'", $field, $search);
        array_push($this->conditions, $conditions);
        return $this;
    }

    public function fetch()
    {
        $result = [];
        $distinct = $this->distinct ? 'DISTINCT' : '';
        $select = implode(',', $this->selectFields);
        $columns = array_keys($this->selectFields);
        if (empty($this->tables)) {
            array_push($this->tables, $this->name);
        }
        $from = implode(',', $this->tables);
        $query = sprintf("SELECT %s %s FROM %s ", $distinct, $select, $from);
        if (!empty($this->conditions)) {
            $query .= "WHERE " . implode(' AND ', $this->conditions);
            $query .= " ";
        }
        if (!empty($this->groupBy)) {
            $query .= "GROUP BY " . implode(',', $this->groupBy);
        }
        $rows = $this->database->query($query);
        foreach ($rows as $row) {
            $item = [];
            foreach ($columns as $key) {
                $item[$key] = $row[$key];
            }
            array_push($result, $item);
        }
        $this->clean();
        return $result;
    }

    protected function clean()
    {
        $this->selectFields = [];
        $this->tables = [];
        $this->conditions = [];
        $this->groupBy = [];
        $this->distinct = false;
    }

}
