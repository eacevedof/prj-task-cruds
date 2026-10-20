<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.3.1
 * @name ComponentPage
 * @date 05-10-2014 12:14 (SPAIN)
 * @observations: Trocea un array según una cantidad de páginas
 */
class ComponentPage
{
    private $items;
    /**
    * Los items por pagina que se deberan mostrar
    *
    * @var mixed
    */

    private $numItemsPerPage;
    private $numRequestPage;
    private $numOfPages;
    private $isExact = true;

    private $uriPagePattern;

    private $isError = false;
    private $message;

    private $isError404 = false;

    private $numTotalItems;
    private $numNextPage;
    private $numPreviousPage;
    private $numLastPage;
    private $numFirstPage;

    public function __construct($items = array(), $numPage = 1, $itemsPerPage = 25, $url = "")
    {
        if (!empty($items)) {
            //bug($iNumPage,"iNumPage");
            $this->uriPagePattern = $url;
            $numPage = $this->avoidNotNumber($numPage);
            $this->numRequestPage = $numPage;
            //actualiza arItems y iNumtotalItems
            $this->setItems($items);
            //actualiza itemps per page
            $this->setItemsPerPage($itemsPerPage);
        } else {
            $this->numTotalItems = 0;
            $this->numNextPage = 1;
            $this->numPreviousPage = 1;
            $this->numLastPage = 1;
            $this->numFirstPage = 1;
        }
    }

    /**
    * Si hay error por rango muestra las cabeceras
    *
    * @param string $sURL  http://www.myurl.com/section/category/page-
    * @param integer $iCode  301|404
    */
    private function sendHeader($code = 301, $url = null, $mensaje404 = "Page not found")
    {
        if (!headers_sent()) {
            switch($code) {
                case 301:
                    header("HTTP/1.1 301 Moved Permanently");
                    header("Location: $url");
                    break;
                case 404:
                    header("HTTP/1.0 404 Not Found");
                    header("Status: 404 Not Found");
                    echo $mensaje404;
                    break;
            }
            exit;
        }
    }

    private function avoidNotNumber($string)
    {
        $entero = $string;
        if (!is_numeric($entero)) {
            $entero = 1;
        }
        return $entero;
    }

    /**
    * Recupera una cantidad de items desde el array que contiene a
    * todos.  Los items de la pagina correspondiente
    * Hace los sets de los atributos de navegacion
    */
    private function getArrayPageItems()
    {
        $pageItems = array();
        //$this->arItems = $this->arItems;
        $this->numTotalItems = count($this->items);
        $this->numFirstPage = 1;
        $this->numNextPage = $this->numRequestPage + 1;
        $this->numPreviousPage = $this->numRequestPage - 1;
        $this->numLastPage = $this->numOfPages;

        //$this->iNumItemsPerPage = $this->iNumItemsPerPage;
        //$this->iNumRequestPage = $this->iNumRequestPage;
        //$this->iNumOfPages = $this->iNumOfPages;

        //bug($iNumPage); bug($iNumOfPages);
        //Si la pagina recibida esta en el rango
        if ($this->numRequestPage > 0 && $this->numRequestPage <= $this->numOfPages) {
            //iKeyInicio = 8 x (4-1) = 24
            $startKey = $this->numItemsPerPage * ($this->numRequestPage - 1);
            //iKeyFinal = 24 + (8 - 1) = 24 + 7 = 31
            //En caso de resto == 0 y que la pagina actual no sea
            //la ultima se puede aplicar el key Final asi
            $lastKey = $startKey + ($this->numItemsPerPage - 1);
            //Si la pagina actual es la ultima
            if ($this->numRequestPage == $this->numOfPages && !$this->isExact) {
                //Las paginas que respetan los items/pagina
                $numOfFullPages = $this->numOfPages - 1;
                //El numero de items distribuidos en estas paginas
                $numItemsDistributed = $numOfFullPages * $this->numItemsPerPage;
                $numItemsLastPage = $this->numTotalItems - $numItemsDistributed;
                $lastKey = $startKey + ($numItemsLastPage - 1);
            }

            for($i = $startKey; $i <= $lastKey; $i++) {
                $pageItems[] = $this->items[$i];
            }
            //bug($arResultado, "get_array_items");
        }
        //else //La Request page no esta en el rango
        //$arPageItems = null;
        return $pageItems;
    }

    /**
    * Calcula el numero de paginas en las que se puede dividir
    * el array completo
    * @return integer iNumPaginas  El numero de paginas en que se dividira $this->_arItems
    */
    public function loadNumOfPages()
    {
        $items = $this->items;
        $itemsPerPage = $this->numItemsPerPage;
        $totalItems = count($items);

        $numOfPages = intval($totalItems / $itemsPerPage);
        $resto = $totalItems % $itemsPerPage;
        //Si el resto es mayor que cero es porque se necesita
        //una pagina mas
        if ($resto > 0) {
            //Con esto indico que la ultima pagina no tendra
            //un listado respetando "itemps/pagina" sino que sera menor
            $this->isExact = false;
            $numOfPages++;
        }

        // bug($iNumOfPages);
        $this->numOfPages = $numOfPages;
    }

    /**
    * Devuelve la lista de todos los botones !< << i, ii, ... n >> >|
    * en html
    */
    private function getNavigationArray()
    {
        $pageButtons = array();
        $numOfPages = $this->numOfPages;
        $reqNumPage = $this->numRequestPage;

        //Hay al menos una pagina
        if ($numOfPages > 0) {
            //En caso que se pase una pagina 0 o negativa (Fuera de rango)
            if ($reqNumPage < 1) {
                //$arButtons = "Error: Page out of range";
                $pageButtons = null;
                $this->addError("Error: Page out of range pagina actual:$reqNumPage, total:$numOfPages");
                $this->isError404 = true;
                //$this->send_header(404);
            }
            //Si solo existe una unica pagina
            elseif ($reqNumPage == 1 && $numOfPages == 1) {
                $pageButtons = null;
            }
            //Si estamos en la primera y hay mas de una
            elseif ($reqNumPage == 1 && $numOfPages > 1) {
                $pageButtons["nav_numbers"] = $this->getButtonsNumbers();
                $pageButtons["nav_next"] = $this->getButtonsNext();
            }
            //Si no es ni la primera ni la ultima
            elseif ($reqNumPage > 1 && $reqNumPage < $numOfPages) {
                $pageButtons["nav_previous"] = $this->getButtonsPrevious();
                $pageButtons["nav_numbers"] = $this->getButtonsNumbers();
                $pageButtons["nav_next"] = $this->getButtonsNext();
            }
            //Si es la ultima
            elseif ($reqNumPage > 1 && $reqNumPage == $numOfPages) {
                $pageButtons["nav_previous"] = $this->getButtonsPrevious();
                $pageButtons["nav_numbers"] = $this->getButtonsNumbers();
            }
            //Si es mayor a la ultima (fuera de rango)
            elseif ($reqNumPage > $numOfPages) {
                $pageButtons = null;
                $this->addError("Error: Page out of range pagina actual:$reqNumPage, total:$numOfPages");
                //hacer redirect 404
                //$this->send_header(404);
                $this->isError404 = true;
            }
        }
        return $pageButtons;
    }

    /**
    * Crea un array con todos los botones que llevan un numero de pagina
    * El boton de la pagina actual se crea sin link
    */
    private function getButtonsNumbers()
    {
        $button = array();
        $numPageNoButton = $this->numRequestPage;
        $numOfPages = $this->numOfPages;
        $url = $this->uriPagePattern;

        for($i = 1; $i <= $numOfPages; $i++) {
            if ($i != $numPageNoButton) {
                //bug($sUrl,"url en get_botones numero");
                //$sTmpUrl = $sUrl . $i ."/";
                $tmpUrl = $url . $i;
                $buttonAnchor = "<li class=\"\"><a href=\"$tmpUrl\" page=\"$numPage\">$i</a></li>";
            } else { //La pagina con el numero que corresponde al boton
                $buttonAnchor = "<li class=\"active\"><a href=\"#\" class=\"$numPage\">$numPageNoButton</a></li>";
            }
            $button[$i] = $buttonAnchor;
        }//fin for iNumOfPages

        return $button;
    }

    private function getButtonsPrevious()
    {
        $button = array("start" => "","end" => "");
        $this->numFirstPage = 1;
        $this->numPreviousPage = $this->numRequestPage - 1;
        //$iReqNumPage = $this->iNumRequestPage;
        //$iNumOfPages = $this->iNumOfPages;
        $url = $this->uriPagePattern;

        //Pagina 1
        //$sTmpUrl = $sUrl. 1 ."/";
        $tmpUrl = $url. $this->numFirstPage;
        $button["start"] = "<li class=\"\"><a href=\"$tmpUrl\" page=\"$this->numFirstPage\">|&lt;</a></li>";

        //Pagina anterior a la actual si la actual es>1
        //$sTmpUrl = $sUrl . ($iReqNumPage-1). "/";
        $tmpUrl = $url . $this->numPreviousPage;
        $button["previous"] = "<li class=\"\"><a href=\"$tmpUrl\" page=\"$this->numPreviousPage\">&lt;&lt;</a></li>";

        return $button;
    }

    /**
     * Sets iNumNextPage and iNumLastPage
     * @return $ar["next"],$ar["end"]
     */
    private function getButtonsNext()
    {
        $button = array("next" => "","end" => "");
        //$iNumLastPage = $this->iNumOfPages;
        //$iNumRequestPage = $this->iNumRequestPage;
        $this->numLastPage = $this->numOfPages;
        $url = $this->uriPagePattern;

        //Solo hay siguiente si la pagina_actual es menor a la ultima pagina (o total de paginas)
        if ($this->numRequestPage < $this->numLastPage) {
            $this->numNextPage = ($this->numRequestPage + 1);
            $tmpUrl = $url . $this->numNextPage;
            $button["next"] = "<li class=\"\"><a href=\"$tmpUrl\" page=\"$this->numNextPage\">&gt;&gt;</a></li>";
        }

        //$sTmpUrl = $sUrl . $iNumLastPage . "/";
        $tmpUrl = $url . $this->numLastPage;
        $button["end"] = "<li class=\"\"><a href=\"$tmpUrl\" page=\"$this->numLastPage\">&gt;|</a></li>";

        return $button;
    }

    /**
    * Devuelve un array indexado por enteros con los items que le corresponde
    * a la pagina $this->iNumRequestPage
    */
    public function getItemsToShow()
    {
        return $this->getArrayPageItems();
    }

    /**
    * Devuelve un array tipo arBotones[anterior], arBotones[siguiente] ...
    */
    public function getButtonsToShow()
    {
        return $this->getNavigationArray();
    }

    /**
    * Devuelve los botones pero en un string. Para poder pintarlos con un echo
    */
    public function getButtonsInHtml()
    {
        $htmlButtons = "";
        $htmlButtons .= "<ul class=\"\">";
        $buttons = $this->getNavigationArray();
        //bug($arButtons,"arbotones en get_buttons_in_html");

        //Navegacion inicio anterior
        if (!empty($buttons["nav_previous"])) {
            $htmlButtons .= $buttons["nav_previous"]["start"];
            $htmlButtons .= $buttons["nav_previous"]["previous"];
        }
        //Navegacion numeros paginas
        if (!empty($buttons["nav_numbers"])) {
            foreach($buttons["nav_numbers"] as $button) {
                $htmlButtons .= $button;
            }
        }

        //Navegacion siguiente final
        if (!empty($buttons["nav_next"])) {
            $htmlButtons .= $buttons["nav_next"]["next"];
            $htmlButtons .= $buttons["nav_next"]["end"];
        }

        $htmlButtons .= "</ul>";
        return $htmlButtons;
    }
    //**********************************
    //             GETS
    //**********************************
    /**
    * Determina si ha ocurrido un error al pasar la pagina.
    * Error de tipo rango
    */
    public function isError()
    {
        return $this->isError;
    }
    public function isError404()
    {
        return $this->isError404;
    }
    public function getMensaje()
    {
        return $this->message;
    }
    public function getTotal()
    {
        return $this->numOfPages;
    }
    public function getTotalRegs()
    {
        return $this->numTotalItems;
    }
    public function getCurrent()
    {
        return $this->numRequestPage;
    }
    public function getNext()
    {
        return $this->numNextPage;
    }
    public function getPrevious()
    {
        return $this->numPreviousPage;
    }
    public function getFirst()
    {
        return $this->numFirstPage;
    }
    public function getLast()
    {
        return $this->numLastPage;
    }
    public function getItemsPerPage()
    {
        return $this->numItemsPerPage;
    }

    //**********************************
    //             SETS
    //**********************************
    private function addError($message)
    {
        $this->isError = true;
        $this->message = $message;
    }

    public function setRequestPage($numPage)
    {
        $this->numRequestPage = $this->avoidNotNumber($numPage);
    }
    public function setUrlPattern($value)
    {
        $this->uriPagePattern = $value;
    }
    public function setItems($items)
    {
        $this->items = $items;
        $this->numTotalItems = count($items);
    }
    public function setItemsPerPage($numItems)
    {
        if (!$numItems) {
            $numItems = 25;
        }
        $this->numItemsPerPage = $numItems;
        $this->loadNumOfPages();
        //Comprobacion de rangos.
        if ($this->numRequestPage <= 0 || $this->numRequestPage > $this->numOfPages) {
            $this->addError("Error: Page out of range. Request page:$this->numRequestPage, total:$this->numOfPages");
        }

    }
}
