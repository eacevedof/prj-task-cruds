<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.0.0
 * @name HelperLink
 * @file HelperLink.php
 * @date 13-05-2017 11:51 (SPAIN)
 * @observations:
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperLink extends TheFrameworkHelper
{
    private $type;//media_type	Specifies the media type of the linked document
    private $rel;//alternate,author,dns-prefetch,help,icon,license,next,pingback
    //,preconnect,prefetch,preload,prerender,prev,search,stylesheet
    private $hrefs;

    public function __construct($hrefs = [], $type = "text/css", $rel = "stylesheet")
    {
        $this->type = $type;
        $this->rel = $rel;
        $this->hrefs = $hrefs;
    }

    public function getHtml()
    {
        $htmlToReturn = "";
        foreach($this->hrefs as $hrefPath) {
            if($hrefPath) {
                $htmlToReturn .= "<link type=\"$this->type\" rel=\"$this->rel\" href=\"$hrefPath\">\n";
            }
        }
        return $htmlToReturn;
    }//get_html

    public function show()
    {
        echo $this->getHtml();
    }

    public function addHref($filePath)
    {
        $this->hrefs[] = $filePath;
    }
    public function setHrefs($href)
    {
        $this->hrefs = [];
        if(is_array($href)) {
            $this->hrefs = $href;
        } else {
            $this->hrefs[] = $href;
        }
    }

    public function getHrefs()
    {
        return $this->hrefs;
    }

}//HelperLink
