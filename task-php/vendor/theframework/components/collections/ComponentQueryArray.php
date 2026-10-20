<?php

class ComponentQueryArray
{
    private $array;
    private $array2;
    private $result;

    public function __construct($data)
    {
        $this->array = $data;
    }

    public function getColum($colname)
    {
        return array_column($this->array, $colname);
    }

    public function where()
    {

    }

    public function setArray2($array)
    {
        $this->array2 = $array;
    }

}//ComponentQueryArray
