<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @name TheFramework\Components\Formatter\ComponentMoment
 * @file ComponentMoment.php 1.1.0
 * @date 07-11-2021 14:03 SPAIN
 * @observations
 */

namespace TheFramework\Components\Formatter;

final class ComponentMoment
{
    private $date = "";
    private $cleaned = "";
    private $ardate = [];
    private $operdate = "";
    private $periods = [];

    private $errors = [];

    public function __construct($date = "")
    {
        $this->date = !$date ? date("Ymd") : trim($date);
        $this->loadExploded();
        $this->loadPeriods();
    }

    private function loadPeriods()
    {
        $this->periods = [
            "d" => ["day","days"],
            "m" => ["month","months"],
            "w" => ["week","weeks"],
            "y" => ["year","years"]
        ];
    }

    private function getAsArray($yyymmdd)
    {
        $ardate = [];
        $numbers = str_split($yyymmdd);

        $tmp = "";
        foreach(range(0, 3) as $i) {
            $tmp .= $numbers[$i];
        }
        $ardate[0] = $tmp;

        $tmp = "";
        foreach(range(4, 5) as $i) {
            $tmp .= $numbers[$i];
        }
        $ardate[1] = $tmp;

        $tmp = "";
        foreach(range(6, 7) as $i) {
            $tmp .= $numbers[$i];
        }
        $ardate[2] = $tmp;

        return $ardate;
    }

    private function loadExploded()
    {
        if (!$this->isValid()) {
            return -1;
        }

        $date = $this->date;
        if (strstr($date, "-")) {
            $this->ardate = explode("-", $date);
        } elseif (strstr($date, "/")) {
            $this->ardate = explode("/", $date);
        } else {
            $this->ardate = $this->getAsArray($date);
        }
        $this->cleaned = implode("", $this->ardate);
        $this->operdate = $this->cleaned;
    }

    private function getStrtotimed($stroperation)
    {
        $strtotime = strtotime($stroperation);
        $newdate = date("Ymd", $strtotime);
        return $newdate;
    }

    private function commonOps($i, $period, $op = "-")
    {
        $stroperation = date($this->cleaned)."$op $i $period";
        $this->operdate = $this->getStrtotimed($stroperation);
        //return $this;
    }

    private function byMonth($i, $sign = "-")
    {
        //los movimientos por meses y años dan "error" para los últimos días de mes por
        //esto tengo que tratarlos manualmente.
        //si se hace esto: 20200331 - 1 month el resultado es 20200301 y debería ser 20200229
        //esto es lo que corrige este metodo
        $thisday = $this->ardate[2];
        $yyyymm01 = $this->ardate[0].$this->ardate[1]."01";
        //todos los segundos pasados hasta la fecha
        $stroperation = strtotime(date($yyyymm01)."$sign $i months");
        $newmonth = date("Ym", $stroperation);
        $newmonth01 = $newmonth."01";
        $newmonththisday = $newmonth.$thisday;
        $newmonthlastday = date("Ymt", strtotime($newmonth01));
        //lg("by_month: yyyymm01:$yyyymm01,newmonth: $newmonth, newmonth01:$newmonth01, newmonthlastday:$newmonthlastday","by_month $i");
        if ($newmonththisday > $newmonthlastday) {
            $this->operdate = $newmonthlastday;
        } else {
            $this->operdate = $newmonththisday;
        }
    }

    public function isValid()
    {
        return (bool) strtotime($this->date);
    }

    public function add($i = 1, $period = "days")
    {
        if (!in_array($period, $this->periods["m"])) {
            $this->commonOps($i, $period, "+");
        } else {
            $this->byMonth($i, "+");
        }
        return $this;
    }

    public function subtract($i = 1, $period = "days")
    {
        if (!in_array($period, $this->periods["m"])) {
            $this->commonOps($i, $period);
        } else {
            $this->byMonth($i);
        }

        return $this;
    }

    public function asMaxdate($today = "")
    {
        if (!$today) {
            $today = date("Ymd");
        }
        if ($this->cleaned > $today) {
            $this->cleaned = $today;
            $this->ardate = $this->getAsArray($this->cleaned);
            $this->operdate = $today;
        }
        return $this;
    }

    public function getCalculated()
    {
        return $this->operdate;
    }

    public function isFullmonth($yyyymmdd)
    {
        $ardate = $this->getAsArray($yyyymmdd);
        //si tienen el mismo día y distinto mes es completo
        return (($this->ardate[1] != $ardate[1]) && ($this->ardate[2] == $ardate[2]));
    }

    public function getNdays($yyymmdd)
    {
        $earlier = new \DateTime($this->cleaned);
        $later = new \DateTime($yyymmdd);
        $diff = $later->diff($earlier)->format("%a");
        return $diff;
    }

    public function getNmins($yyymmddhis)
    {
        $earlier = new \DateTime($this->cleaned);
        $later = new \DateTime($yyymmddhis);
        $diff = ($later->getTimestamp() - $earlier->getTimestamp()) / 60;
        return $diff;
    }

    public function getDay()
    {
        return $this->ardate[2];
    }
    public function getMonth()
    {
        return $this->ardate[1];
    }
    public function getYear()
    {
        return $this->ardate[0];
    }

    public function getLastday()
    {
        return date("Ymt", strtotime($this->cleaned));
    }

    public function hasDay($day)
    {
        $yyyymmdd = $this->getLastday();
        $ardate = $this->getAsArray($yyyymmdd);
        return ($day <= $ardate[2]);
    }

    public function isGreaterThan($date)
    {
        $odatein = new \DateTime($this->date);
        $odatex = new \DateTime($date);
        return $odatein > $odatex;

    }
}
