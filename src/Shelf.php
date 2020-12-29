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
    protected $select = [];
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

    public function count()
    {
        $query = sprintf("SELECT COUNT(*) AS count FROM `%s`", $this->name);
        $rows = $this->database->query($query);
        $row = $rows->fetchArray();
        $numRows = $row['count'];
        return $numRows;
    }

    public function select($fields = [])
    {
        foreach ($fields as $alias => $field) {
            if (is_string($alias)) {
                $column = sprintf("json_extract(document, '$.%s') AS %s ", $field, $alias);
                $this->select[$alias] = $column;
            } else {
                $column = sprintf("json_extract(document, '$.%s') AS %s", $field, $field);
                $this->select[$field] = $column;
            }
        }

        return $this;
    }

    public function selectDistinct($fields = [])
    {
        $this->distinct = true;
        return $this;
    }

    public function fetch()
    {
        $dataset = [];
        $distinct = $this->distinct ? 'DISTINCT' : '';
        $select = implode(',', $this->select);
        $columns = array_keys($this->select);
        $query = sprintf("SELECT %s %s FROM %s", $distinct, $select, $this->name);
        $results = $db->query($query);
        while ($row = $results->fetchArray()) {
            $item = [];
            foreach ($columns as $key) {
                $item[$key] = $row[$key];
            }
            array_push($dataset, $item);
        }
        return $dataset;
    }

}
