<?php

/**
 * @copyright Copyright (c) 2020 Alonso Mora
 * @license   https://github.com/amoracr/yii2-backup/blob/master/LICENSE.md
 * @link      https://github.com/amoracr/yii2-backup#readme
 * @author    Alonso Mora <adelfunscr@gmail.com>
 */

namespace amoracr\nosqlite;

use amoracr\nosqlite\Rack;
use yii;
use \DirectoryIterator;

/**
 *
 *
 * @author Alonso Mora <adelfunscr@gmail.com>
 * @since 1.0
 */
class Client
{

    protected $path;
    protected $racks = [];

    public function __construct($path)
    {
        $this->setPath($path);
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function createRack($rackname)
    {
        if (!array_key_exists($rackname, $this->racks)) {
            $dnsRack = sprintf('%s' . DIRECTORY_SEPARATOR . '%s.nosqlite', $this->path, $rackname);
            $this->racks[$rackname] = new Rack($dnsRack);
        }
    }

    public function listRacks()
    {
        $path = Yii::getAlias($this->path);
        $racks = [];
        foreach (new DirectoryIterator($path) as $fileInfo) {
            if ($fileInfo->getExtension() === 'nosqlite') {
                $racks[] = $fileInfo->getBasename('.nosqlite');
            }
        }

        return $racks;
    }

    public function selectRack($rackname)
    {
        if (!array_key_exists($rackname, $this->racks)) {
            $this->createRack($rackname);
        }
        return $this->racks[$rackname];
    }

    public function selectShelf($rackname, $shelfname)
    {
        return $this->selectRack($rackname)->selectShelf($shelfname);
    }

}
