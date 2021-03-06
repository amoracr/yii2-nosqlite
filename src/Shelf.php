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
    protected $andConditions = [];
    protected $groupBy = [];
    protected $distinct = false;
    protected $query;

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
        $condition = sprintf("json_extract(document, '$.%s') = '%s'", $field, $search);
        array_push($this->andConditions, $condition);
        return $this;
    }

    public function whereLike($field, $search)
    {
        $condition = sprintf("json_extract(document, '$.%s') LIKE ", $field, $search);
        $condition .= "'%$search%' ";
        array_push($this->andConditions, $condition);
        return $this;
    }

    public function whereBetween($field, $bottom = 0, $top = 1)
    {
        $condition = sprintf("json_extract(document, '$.%s') BETWEEN  %d AND %d ", $field, $bottom, $top);
        array_push($this->andConditions, $condition);
        return $this;
    }

    public function whereIn($field, $values = [])
    {
        $list = array_map(function($element) {
            return "'$element'";
        }, $values);
        $list = implode(',', $list);
        $condition = sprintf("json_extract(document, '$.%s') IN ( %s )", $field, $list);
        array_push($this->andConditions, $condition);
        return $this;
    }

    public function whereNotIn($field, $values = [])
    {
        $list = array_map(function($element) {
            return "'$element'";
        }, $values);
        $list = implode(',', $list);
        $condition = sprintf("json_extract(document, '$.%s') NOT IN ( %s )", $field, $list);
        array_push($this->andConditions, $condition);
        return $this;
    }

    public function customQuery($query = '')
    {
        $this->query = $query;
        return $this;
    }

    public function fetch()
    {
        $result = [];
        if (empty($this->query)) {
            $this->prepareFetchQuery();
        }
        $rows = $this->database->query($this->query);
        if (empty($this->selectFields)) {
            foreach ($rows as $row) {
                array_push($result, $row);
            }
        } else {
            $columns = array_keys($this->selectFields);
            foreach ($rows as $row) {
                $item = [];
                foreach ($columns as $key) {
                    $item[$key] = $row[$key];
                }
                array_push($result, $item);
            }
        }
        $this->clean();
        return $result;
    }

    protected function clean()
    {
        $this->selectFields = [];
        $this->tables = [];
        $this->andConditions = [];
        $this->groupBy = [];
        $this->distinct = false;
        $this->query = '';
    }

    protected function prepareFetchQuery()
    {
        $distinct = $this->distinct ? 'DISTINCT' : '';
        $select = !empty($this->selectFields) ? implode(',', $this->selectFields) : '*';
        if (empty($this->tables)) {
            array_push($this->tables, $this->name);
        }
        $from = implode(',', $this->tables);
        $query = sprintf("SELECT %s %s FROM %s ", $distinct, $select, $from);
        if (!empty($this->andConditions)) {
            $query .= "WHERE " . implode(' AND ', $this->andConditions);
            $query .= " ";
        }
        if (!empty($this->groupBy)) {
            $query .= "GROUP BY " . implode(',', $this->groupBy);
        }
        $this->query = $query;
    }

}
