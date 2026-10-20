<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @name TheApplication\Components\ComponentConfig
 * @file ComponentConfig.php 1.0.0
 * @date 04-06-2020 12:35 SPAIN
 * @observations
 */

namespace TheFramework\Components\Config;

class Node
{
    private $arnode;

    public function __construct($arnode)
    {
        $this->arnode = $arnode;
    }

    public function findByKey($key, $value)
    {
        $node = $this->arnode;
        $keys = array_keys($node);
        if (!in_array($key, $keys)) {
            return null;
        }

        if ($node[$key] == $value) {
            return $node;
        }
    }
}


class ComponentConfig
{
    private $arcontent;

    public function __construct($path)
    {
        $this->loadcontent($path);
    }

    private function loadcontent($path)
    {
        $isfile = is_file($path);
        if (!$isfile) {
            return;
        }
        $this->arcontent = \json_decode(file_get_contents($path), 1);
    }

    public function getNode($key, $value)
    {
        foreach ($this->arcontent as $arnode) {
            $objnode = (new Node($arnode))->findByKey($key, $value);
            if ($objnode !== null) {
                return $arnode;
            }
        }
        return [];
    }



}//ComponentConfig
