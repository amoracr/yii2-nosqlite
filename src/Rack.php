<?php

/**
 * @copyright Copyright (c) 2020 Alonso Mora
 * @license   https://github.com/amoracr/yii2-backup/blob/master/LICENSE.md
 * @link      https://github.com/amoracr/yii2-backup#readme
 * @author    Alonso Mora <adelfunscr@gmail.com>
 */

namespace amoracr\nosqlite;

use yii;
use \PDO;

/**
 *
 *
 * @author Alonso Mora <adelfunscr@gmail.com>
 * @since 1.0
 */
class Rack
{

    /**
     * @var PDO object
     */
    protected $connection;

    /**
     * @var string
     */
    protected $dbPath;
    protected $shelves = [];

    public function __construct($path, $options = [])
    {
        $this->dbPath = Yii::getAlias($path);
        $dns = sprintf("sqlite:%s", $this->dbPath);
        $this->connection = new PDO($dns, null, null, $options);
    }

    public function drop()
    {
        if (file_exists($this->dbPath)) {
            @unlink($this->dbPath);
        }
    }

    public function createShelf($shelfname)
    {
        if (!array_key_exists($shelfname, $this->shelves)) {
            $query = sprintf("CREATE TABLE IF NOT EXISTS `%s` (id integer PRIMARY KEY AUTOINCREMENT NOT NULL, document json) ", $shelfname);
            $this->connection->exec($query);
            $this->shelves[$shelfname] = new Shelf($shelfname, $this->connection);
        }
    }

    public function dropShelf($shelfname)
    {
        if (array_key_exists($shelfname, $this->shelves)) {
            $query = sprintf("DROP TABLE IF EXISTS `%s`", $shelfname);
            $this->connection->exec($query);
            unset($this->shelves[$shelfname]);
        }
    }

    public function listShelves()
    {
        $result = [];
        $query = "SELECT name FROM sqlite_master WHERE type='table' AND name != 'sqlite_sequence'";
        $rows = $this->connection->query($query);
        foreach ($rows as $row) {
            array_push($result, $row['name']);
        }
        return $result;
    }

    public function selectShelf($shelfname)
    {
        if (!array_key_exists($shelfname, $this->shelves)) {
            $this->createShelf($shelfname);
        }
        return $this->shelves[$shelfname];
    }

}
