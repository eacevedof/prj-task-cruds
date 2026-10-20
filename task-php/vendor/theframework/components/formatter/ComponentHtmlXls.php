<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.0.2
 * @name ComponentHtmlXls
 * @file ComponentHtmlXls.php
 * @date 15-08-2014 13:55 (SPAIN)
 * @observations:
 * @requires:
 *  helper_table_td.php,
 *  helper_table_tr.php,
 *  helper_table.php
 */
import_helper("table");

class ComponentHtmlXls
{
    //
    protected $header;
    protected $data;
    protected $table;
    protected $fileName;

    public function __construct($data, $header = array())
    {
        $this->data = $data;
        $this->header = $header;
        $this->table = new HelperTable();
    }

    private function buildHead()
    {
        if ($this->header) {
            $tmpTr = new HelperTableTr();
            foreach($this->header as $headLabel) {
                $TmpTd = new HelperTableTd();
                $TmpTd->setInnerhtml($headLabel);
                $tmpTr->addInnerObject($TmpTd);
            }
            $this->table->addTr($tmpTr);
        }
    }//build_head

    private function buildData()
    {
        if ($this->data) {
            foreach($this->data as $row) {
                $tmpTr = new HelperTableTr();
                foreach($row as $fieldValue) {
                    $tmpTd = new HelperTableTd();
                    $tmpTd->setInnerhtml($fieldValue);
                    $tmpTr->addTd($tmpTd);
                }
                $this->table->addTr($tmpTr);
            }
        }//
    }//build_data

    private function dataReorder()
    {
        $list = array();
        if ($this->header) {
            foreach($this->header as $fileNameH => $label) {
                foreach($this->data as $row => $values) {
                    $list[$row][$fileNameH] = $values[$fileNameH];
                }//foreach $arList
            }//foreach $this->arHeader
            $this->data = $list;
        }
    }

    public function downloadXls()
    {
        $this->buildHead();
        $this->dataReorder();
        $this->buildData();
        if ($this->fileName) {
            $fileName = $this->fileName."_";
        }
        $fileName = $fileName.date("YmdHis").".xls";
        header("Content-type: application/vnd.ms-excel");
        header("Content-Disposition: attachment;filename=$fileName");
        $this->table->show();
        exit();//evita imprimir debug
    }//download_xls

    //=======================
    //         SETS
    //=======================
    public function setHeader($header)
    {
        $this->header = $header;
    }
    public function setData($data)
    {
        $this->data = $data;
    }
    public function setFieldname($fieldName)
    {
        $this->fileName = $fieldName;
    }
    //=======================
    //        GETS
    //=======================
}
