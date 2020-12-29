<?php

/**
 * @copyright Copyright (c) 2020 Alonso Mora
 * @license   https://github.com/amoracr/yii2-backup/blob/master/LICENSE.md
 * @link      https://github.com/amoracr/yii2-backup#readme
 * @author    Alonso Mora <adelfunscr@gmail.com>
 */

namespace amoracr\nosqlite;

use yii;

/**
 *
 *
 * @author Alonso Mora <adelfunscr@gmail.com>
 * @since 1.0
 */
class Rack
{

    /**
     * @var string - DSN path form memory database
     */
    const DSN_PATH_MEMORY = ':memory:';

    /**
     * @var PDO object
     */
    protected $connection;

    /**
     * @var string
     */
    protected $dbPath;
    protected $shelves = [];

    public function __construct($path = self::DSN_PATH_MEMORY, $options = [])
    {
        $this->dbPath = \Yii::getAlias($path);
        $dns = sprintf("sqlite:%s", $this->dbPath);
        $this->connection = new \PDO($dns, null, null, $options);
    }

    public function drop()
    {
        if ($this->dbPath != self::DSN_PATH_MEMORY) {
            \unlink($this->dbPath);
        }
    }

    public function createShelf($shelfname)
    {
        if (empty($shelfname) || array_key_exists($shelfname, $this->shelves)) {
            return;
        }
        $query = sprintf("CREATE TABLE IF NOT EXISTS `%s` (id integer PRIMARY KEY AUTOINCREMENT NOT NULL, document json) ", $shelfname);
        $this->connection->exec($query);
        if (!array_key_exists($shelfname, $this->shelves)) {
            $this->shelves[$shelfname] = new Shelf($shelfname, $this->connection);
        }
    }

    public function dropShelf($shelfname)
    {
        if (empty($shelfname) || !array_key_exists($shelfname, $this->shelves)) {
            return;
        }
        $query = sprintf("DROP TABLE IF EXISTS `%s`", $shelfname);
        $this->connection->exec($query);
        if (array_key_exists($shelfname, $this->shelves)) {
            unset($this->shelves[$shelfname]);
        }
    }

    public function getShelvesNames()
    {
        
    }

    public function selectShelf($shelfname)
    {
        $this->createShelf($shelfname);
        return $this->shelves[$shelfname];
    }

}
