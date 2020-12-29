<?php

/**
 * @copyright Copyright (c) 2020 Alonso Mora
 * @license   https://github.com/amoracr/yii2-backup/blob/master/LICENSE.md
 * @link      https://github.com/amoracr/yii2-backup#readme
 * @author    Alonso Mora <adelfunscr@gmail.com>
 */

namespace amoracr\nosqlite;

use amoracr\nosqlite\Rack;

/**
 *
 *
 * @author Alonso Mora <adelfunscr@gmail.com>
 * @since 1.0
 */
class Client
{

    protected $path;

    public function __construct($path = Rack::DSN_PATH_MEMORY)
    {
        $this->setPath($path);
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function createRack($rackname)
    {
        $dnsRack = sprintf('%s' . DIRECTORY_SEPARATOR . '%s.nosqlite', $this->path, $rackname);
        $rack = new Rack($dnsRack);
    }

    public function listRacks()
    {
        
    }

    public function selectRack($rackname)
    {
        $dnsRack = sprintf('%s' . DIRECTORY_SEPARATOR . '%s.nosqlite', $this->path, $rackname);
        return new Rack($dnsRack);
    }

    public function selectShelf($rackname, $shelfname)
    {
        
    }

}
