<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.0.0
 * @name ComponentXml
 * @file ComponentXml.php
 * @date 17-07-2015 10:41 (SPAIN)
 * @observations:
 */
class ComponentXml
{
    public function __construct()
    {
        ;
    }

    public function buildHead($encode = "utf-8")
    {
        $xmlTag = "";
        $xmlTag .= "<?xml ";
        $xmlTag .= "version=\"1.0\" encoding=\"$encode\" standalone=\"no\"?>\r\n";

        //<Document xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.03">
        //        $sXmlTag .= "<Document xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" ";
        //        $sXmlTag .= "xmlns=\"urn:iso:std:iso:20022:tech:xsd:pain.001.001.03\">\r\n";
        return $xmlTag;
    }//build_head

    private function escapeXmlstring($toEscape)
    {
        if ($toEscape) {
            $replaces = array(
                "&" => "&amp;",">" => "&gt;","<" => "&lt;",
                "'" => "&apos;","\"" => "&quot;"," " => "&#160;",
            );

            foreach($replaces as $search => $replace) {
                $toEscape = str_replace($search, $replace, $toEscape);
            }
        }
        return $toEscape;
    }//escape_xmlstring

    public function buildComment($comment)
    {
        $xmlTag = "<!-- ";
        $xmlTag .= $comment;
        $xmlTag .= " -->\r\n";
        return $xmlTag;
    }

    public function buildTagOpen($tagName, $tagParams = "")
    {
        $xmlTag = "";
        $xmlTag .= "<";
        $xmlTag .= $tagName;
        if ($tagParams) {
            $xmlTag .= " ".$tagParams;
        }
        $xmlTag .= ">\r\n";
        return $xmlTag;
    }

    public function buildTagClose($tagName)
    {
        return "</".$tagName.">\r\n";
    }

    public function buildTagSimple($tagName, $innerValue)
    {
        $xmlTag = $this->buildTagOpen($tagName);
        $xmlTag .= $innerValue;
        $xmlTag .= $this->buildTagClose($tagName);
        return $xmlTag;
    }

    public function buildTagParams($tagName, $innerValue, $tagParams = "")
    {
        $xmlTag = $this->buildTagOpen($tagName, $tagParams);
        $xmlTag .= $innerValue;
        $xmlTag .= $this->buildTagClose($tagName);
        return $xmlTag;
    }

    public function buildTagScaped($tagName, $innerValue, $tagParams = "")
    {
        $xmlTag = $this->buildTagOpen($tagName, $tagParams);
        $xmlTag .= $this->escapeXmlstring($innerValue);
        $xmlTag .= $this->buildTagClose($tagName);
        return $xmlTag;
    }

    //=======================
    //         SETS
    //=======================

    //=======================
    //        GETS
    //=======================
}//ComponentXml 1.0.0
