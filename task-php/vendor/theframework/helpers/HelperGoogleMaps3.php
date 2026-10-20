<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.1.11
 * @name Helper GoogleMaps3
 * @date 27-10-2014 14:50
 * @file helper_google_maps_3.php
 * @requires js_google_map_3.js v1.1.4, jquery v1.7+
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperGoogleMaps3
{
    //=====================
    //      ATRIBUTOS
    //=====================
    //Signatura codificada. Se puede proporcionar una o se puede generar a partir de criptokey y clientid
    private $_sSignature = "";
    private $_useSignature = false;
    //Una criptokey se proporciona cuando se contrata el plan empresarial
    private $_sCriptokey = "";
    private $_useCriptoKey = false;
    //Un clientid se proporciona cuando se contrata el plan empresarial
    private $_sClientId = "";
    //Si el client id lo van a utilizar distintas apps para diferenciar las llamadas se utiliza un canal.
    private $_sChannel = "";

    //Apikey son las claves no empresariales
    private $_sApikey = "";
    //Indica si se cargara automaticamente el tag "<script>" con la url
    //de googlemaps. Si se ha definido una apikey se incluye
    private $_useApikey = true;
    //Indica si se va a incluir la libreria jquery desde google.
    //Si dispones de una copia local se puede desactivar.
    private $_useGoogleJquery = true;

    //FOR JS
    //Estos son parametros de configuración para el archivo: js_google_map_3.js

    //Mapa
    private $_sMapType = "'roadmap'"; //El tipo de mapa.
    //Punto inicial de muestreo. Cuando se crea el mapa debe mostrar algún punto
    //he escogido las coord. de la Puerta del Sol (Madrid)
    private $_fLatitude = 40.41694;
    private $_fLongitude = -3.70361;
    //El zoom
    private $_iZoom = 6;

    //Marcadores
    private $_arMarkers = "[]"; //Las chinchetas
    private $_useMakersNumbers = true; //Si las chinchetas mostrarán números.
    private $_sMarkerColor = "green";

    //Lineas
    //Indica si ha de pintarse lineas entre las chinchetas (Markers).
    private $_drawLines = false;

    //Lienzo
    //Elemento div donde se mostrará el mapa generado.
    private $_sIdDivContainer = "'map_canvas'";
    //Configuración del tamaño del mapa y su unidad.
    private $_iWidth = 800;
    private $_iHeight = 600;
    private $_sUnitWH = "px";

    //Rutas
    private $_sRouteMode = "driving";
    private $_drawRoutes = false;
    private $_sRouteColor = "green";
    private $_fRouteAlpha = 0.5;
    private $_iRouteWidth = 3;
    //FIN FOR JS


    //FOR PHP
    //Url para calculo de distancia en km y tiempo entre dos marcadores
    // 173.194.67.95 (era 173.194.78.95) en lugar de maps.googleapis.com porque el firewall lo bloquea
    //private $_sUrlApiDistanceMatrix = "http://173.194.67.95/maps/api/distancematrix/xml?";
    private $_sUrlApiDistanceMatrix = "http://maps.googleapis.com/maps/api/distancematrix/xml?";
    //Parametros para hacer peticiones de latitud y longitud de una dirección
    //Url para enviar peticiones por get. Devolvera un objeto xml
    private $_sUrlApiGeocode = "http://maps.googleapis.com/maps/api/geocode/xml";
    //Indica si se ha de acotar la busqueda de una dirección.
    //Evita que te encuentre una dirección equivalente en otro país.
    private $_doNarrowSearch = true;
    //Coordenadas de acotación de España. Peninsula y Canarias.
    //private $_arNarrowLat = array("min"=>24.654534254781115,"max"=>45.18786629495072);
    //private $_arNarrowLong = array("min"=>-19.80908203125, "max"=>4.3828125);
    private $_arNarrowLat = array("min" => 35,"max" => 43); //Eje Y
    private $_arNarrowLong = array("min" => -9, "max" => 4); //Eje X

    //TODO: Direcciones a geolocalizar. Lo dejaré para otra versión.
    private $_arAddresses = array();
    //Indica si se ha de esperar x microsegundos a que responda la url de petición
    private $_useDelay = true;
    private $_iDelayTime = 250;

    //Indicadores del estado de la petición de geolocalización.
    private $_is_error = false;
    private $_message = "";
    private $_arRoutes = array();

    /**
     * Formato del array de marcadores:
       $arMarkers[]=array
       (
            "title"=>"un titulo ", Lo que se mostrara al pasar el raton
            "content"=>"<b>Un contenido</b>", El texto dentro del popup de dialogo
            "latitude"=>"36.643271085342",
            "longitude"=>"-4.578660819468"
        );
     * @param array $arRutas Array anidado tipo tabla filas x columnas
     * @param array $arAddresses TODO próxima entrega ;0)
     */
    public function __construct($rutas = array(), $addresses = array(), $apikey = "")
    {
        $this->_arRoutes = $rutas;
        $this->_arAddresses = $addresses;
        if(!empty($apikey)) {
            $this->_sApikey = $apikey;
        }
    }

    //Pasa al objeto javascript los datos configurados en esta clase y despues
    //ejecuta load_map(), función javascript que dibuja el mapa con los datos
    //de configuración.
    public function drawMap()
    {
        //bug($this); die;
        if($this->_useGoogleJquery) {
            $this->showGoogleJqueryTag();
        }
        //Muestra el tag <script> que importa la api v3 en js de gmaps
        $this->showJsapiV3Tag();
        ?>
<script type="text/javascript" src="http://google-maps-utility-library-v3.googlecode.com/svn/trunk/styledmarker/src/StyledMarker.js"></script>
<script type="text/javascript">
    //Configurando el objeto gmaps3
    //Mapa
    gmaps3.config.sMapType = <?php $this->getMaptype(); ?>;
    gmaps3.config.fLatitude = <?php $this->getLatitude(); ?>;
    gmaps3.config.fLongitude = <?php $this->getLongitude(); ?>;
    gmaps3.config.iZoom = <?php $this->getZoom(); ?>;
    //Rutas
    gmaps3.config.arRoutes = <?php $this->showJsArrayRoutes(); ?>;
    gmaps3.config.useMarkersNumbers = <?php $this->isMakersWithNumbers(); ?>;
    //gmaps3.config.sMarkerColor = <?php $this->getMarkerColor(); ?>;
    //Lineas
    gmaps3.config.drawLines = <?php $this->doDrawLines(); ?>;
    //Lienzo
    gmaps3.config.sIdDivContainer = <?php $this->getDivContainer(); ?>;
    gmaps3.config.iHeight = <?php $this->getHeight(); ?>;
    gmaps3.config.iWidth = <?php $this->getWidth(); ?>;
    gmaps3.config.sUnitWH = <?php $this->getSizeUnit(); ?>;
    //Rutas
    gmaps3.config.sRouteMode = <?php $this->getRouteType(); ?>;
    gmaps3.config.drawRoutes = <?php $this->doDrawRoutes(); ?>;
    gmaps3.config.sRouteColor = <?php $this->getRouteColor(); ?>;
    gmaps3.config.iRouteWidth = <?php $this->getRouteWidth(); ?>;
    gmaps3.config.fRouteAlpha = <?php $this->getRouteAlpha(); ?>;
    
    //bug(gmaps3,"gmaps3"); //La función bug es equivalente a console.debug
    //bug(gmaps3.config.arMarkers,"markers");
    jQuery(document).ready(gmaps3.load_map);
</script>
<?php
    }

    /**
     * Imprime en pantalla un array javascript de dos dimensiones.
     */
    public function showJsArrayRoutes()
    {
        echo $this->getJsAsArrayFromRoutes();
    }

    private function getJsAsArrayFromRoutes()
    {
        $jsRoutes = array();
        $jsArray = "[";
        foreach($this->_arRoutes as $routeData) {
            $jsRoute = "[";
            //Todos los puntos con sus datos
            $jsRoute .= $this->getJsAsArrayTableFromMarkers($routeData["dots"]);
            //Los indices de esos puntos que indican que son las paradas
            $jsRoute .= ",".$this->getJsAsArrayListOfStops($routeData["stops"]);
            $jsRoute .= ",".$this->getAsJsString($routeData["pincolor"]);
            $jsRoute .= ",".$this->getAsJsString($routeData["tracecolor"]);
            $jsRoute .= "]";
            $jsRoutes[] = $jsRoute;
        }
        if(!empty($jsRoutes)) {
            $jsArray .= implode(",", $jsRoutes);
        }
        //$arMarkers = $arRoutes
        $jsArray .= "]";
        return $jsArray;
    }

    private function getJsAsArrayTableFromMarkers($markers)
    {
        if(empty($markers)) {
            $markers = $this->_arMarkers;
        }
        $items = array();
        $jsArray = "[";
        foreach($markers as $row) {
            $items[] = $this->getAsJsArrayRow($row);
        }

        if(!empty($items)) {
            $jsArray .= implode(",", $items);
        }
        $jsArray .= "]";
        return $jsArray;
    }

    private function getJsAsArrayListOfStops($stops)
    {
        $jsArray = "[";
        $jsArray .= implode(",", $stops);
        $jsArray .= "]";
        return $jsArray;
    }

    /* DEPRECATED
     * Imprime en pantalla un array javascript de dos dimensiones.
     */
    private function showJsArrayTable()
    {
        echo $this->getJsAsArrayTable();
    }

    /* DEPRECATED
     * Devuelve un string js con el formato de un array generado desde
     * $this_arMarkers
     */
    private function getJsAsArrayTable()
    {
        $table = $this->_arMarkers;
        //bug($arTable,"a pasar a js");
        $items = array();
        $jsArray = "[";
        foreach($table as $row) {
            $items[] = $this->getAsJsArrayRow($row);
        }

        if(!empty($items)) {
            $jsArray .= implode(",", $items);
        }
        $jsArray .= "]";
        return $jsArray;
    }

    /**
     * Se le pasa un array de tipo columnas y lo convierte en array js
     * @param array $arRowMarker tipo array("nombre_columna"=>"valor",..,)
     * @return string Cadena de texto en formato array
     */
    private function getAsJsArrayRow($rowMarker = array())
    {
        $items = array();
        $jsArray = "[";
        foreach($rowMarker as $key => $fieldValue) {
            switch($key) {
                case "content":
                case "title":
                    $items[] = $this->getAsJsString($fieldValue);
                    break;
                case "number":
                case "latitude":
                case "longitude":
                case "zindex":
                    $items[] = $fieldValue;
                    break;
            }
        }
        if(!empty($items)) {
            $jsArray .= implode(",", $items);
        }
        $jsArray .= "]\n";
        return $jsArray;
        //['sTitle', fLat, fLong, iZindex, sContent, iIconNumber]
    }

    /**
     * Se utiliza para generar un array de substrings
     * @param string $sValue
     * @return string algo como '1'. Asi js lo entenderá como un string.
     */
    private function getAsJsString($value)
    {
        return "'$value'";
    }

    /**
     *
     * @param array $arPoint1 array("latitude"=>valor,"longitude"=>valor)
     * @param array $arPoint2 array("latitude"=>valor,"longitude"=>valor)
     * @return float distancia redondeada a dos decimales.
     */
    public function distanceCalculation(array $point1, array $point2)
    {
        $x1 = $point1["latitude"];
        $y1 = $point1["longitude"];
        $x2 = $point2["latitude"];
        $y2 = $point2["longitude"];

        if(is_numeric($x1) && is_numeric($y1) && is_numeric($x2) && is_numeric($y2)) {
            $x1 = sqrt(pow(($x1 - $x2), 2) + pow(($y1 - $y2), 2));
        }
        $x1 = round($x1, 2);
        return (float)$x1;
    }

    /**
     * Calcula el tiempo y distancia entre dos puntos
     * @param array $arPoint1 array(latitude, longitude)
     * @param array $arPoint2 array(latitude, longitude)
     */
    public function getDistanceAndTime(array $point1, array $point2)
    {
        //bug($arPoint1); bug($arPoint2);
        $timeDistance = array("time" => "","distance" => "");
        $x1 = $point1["latitude"];
        $y1 = $point1["longitude"];
        $x2 = $point2["latitude"];
        $y2 = $point2["longitude"];

        $params = array();

        if(!empty($this->_sClientId)) {
            $params["clientid"] = "client=$this->_sClientId";
        }
        if(!empty($this->_sChannel)) {
            $params["channel"] = "channel=$this->_sChannel";
        }
        $params["sensor"] = "sensor=false";
        //La tuberia me fastidiaba la peticion. Solo se debe usar para los arrays de puntos.
        $params["origins"] = "origins=$x1,$y1"; //longitud, latitud
        $params["destinations"] = "destinations=$x2,$y2";
        $params["mode"] = "mode=driving";
        $params["language"] = "language=es-ES";
        //este parametro ayuda a diferenciar las distintas aplicaciones que hacen la petición

        $params = implode("&", $params);
        //http://maps.googleapis.com/maps/api/distancematrix/xml?
        $urlDistanceOrig = $this->_sUrlApiDistanceMatrix.$params;
        $urlDistanceSigned = $this->getUrlByKeypriority($urlDistanceOrig);
        //writelog("bd_ask",$sUrlDistance,"distancematrix request");

        //$sUrlDistance = "http://173.194.67.95/maps/api/distancematrix/xml?origins=40.356036037274,-3.686662625685&destinations=40.356083897923,-3.686544105619&mode=driving&language=es-ES&sensor=false&client=gme-telynetsa&signature=XBqNzocRJzYp0gIFi4Dx0kUoxd4=";
        $xml = simplexml_load_file($urlDistanceSigned);
        //Si no ha funcionado la url con firma. Se prueba la gratuita. A veces funciona con
        if($xml == false) {
            writelog("bd_ask", $urlDistanceSigned, "distancematrix error obtencion xml");
            $urlDistanceSigned = $urlDistanceOrig;
            //llamada con url gratuita
            $xml = simplexml_load_file($urlDistanceSigned);
        }
        //bug($oXml,"xml distance matrix resultado $sUrlDistance"); die;
        if($xml != false) {
            $xmlStatus = $xml->status;
            //bug($oXmlStatus,"xmlstatus $sUrlDistance");
            //status ok. Se ha localizado la dirección
            if(strcmp($xmlStatus, "OK") == 0) {

                $timeDistance["time"]["min"] = (string)$xml->row->element->duration->text;//v3
                $timeDistance["time"]["sec"] = (string)$xml->row->element->duration->value;
                $timeDistance["distance"]["m"] = (string)$xml->row->element->distance->value;//v3
                //los kilomentros se redondean a dos decimales. 172m = 0.2km deberia ser 0.17 km
                //$arTimeDistance["distance"]["km"] = (string)$oXml->row->element->distance->text;//v3

                //fkm flotante para calculos matematicos.
                $distanceInKm = ((float)$timeDistance["distance"]["m"]) / 1000;
                $distanceInKm = number_format($distanceInKm, 3);
                $timeDistance["distance"]["fkm"] = $distanceInKm;
                //km en formato internacionalizacion España
                $timeDistance["distance"]["km"] = number_format($distanceInKm, 2);
                $timeDistance["distance"]["km"] = str_replace(".", ",", $timeDistance["distance"]["km"]);
                $timeDistance["distance"]["km"] .= " km";
                writelog("bd_ask", $urlDistanceSigned, "distancematrix ok");
            }
            //status de xml fallido
            else {
                $this->setMessageError("La distancia entre $params[origin] y $params[destination] no se pudo calcular. Estado=$xmlStatus");
                writelog("bd_ask", $urlDistanceSigned, "distancematrix xml status fallido");
            }
        }
        //error en simplexml_load_file
        else {
            $this->setMessageError("No se ha podido crear xml desde: $urlDistanceSigned");
            writelog("bd_ask", $urlDistanceSigned, "distancematrix error obtencion xml");
        }
        //bug($oXml->row->element->distance,"distance object");
        //bug($arTimeDistance);
        return $timeDistance;
    }

    /*
     * Dependiendo de los datos de las chinchetas se va calculando las distancias
     * ojo, NO EL RECORRIDO.
     */
    public function sumDistance()
    {
        $point1 = array("latitude" => 0,"longitude" => 0);
        $point2 = array("latitude" => 0,"longitude" => 0);
        $numMarkers = count($this->_arMarkers);
        $distance = 0;
        for($i = 0; $i < $numMarkers - 1; $i++) {
            $point1["latitude"] = $this->_arMarkers[$i]["latitude"];
            $point1["longitude"] = $this->_arMarkers[$i]["longitude"];
            $point2["latitude"] = $this->_arMarkers[$i + 1]["latitude"];
            $point2["longitude"] = $this->_arMarkers[$i + 1]["longitude"];
            $distance += $this->distanceCalculation($point1, $point2);
        }
        return (float)$distance;
    }

    private function isDistanceInKm($text)
    {
        if(strpos($text, "km") !== false) {
            return true;
        }
        return false;
    }

    //http://gmaps-samples.googlecode.com/svn/trunk/urlsigning/UrlSigner.php-source
    //GOOGLE owns: Encode a string to URL-safe base64
    private function encodeBase64UrlSafe($value)
    {
        return str_replace(array("+", "/"), array("-", "_"), base64_encode($value));
    }

    // Decode a string from URL-safe base64
    //GOOGLE owns
    private function decodeBase64UrlSafe($value)
    {
        return base64_decode(str_replace(array("-", "_"), array("+", "/"), $value));
    }

    // Sign a URL with a given crypto key
    // Note that this URL must be properly URL-encoded
    //GOOGLE owns
    public function getEncodedSignature($urlEncoded, $cryptokey)
    {
        //parse_url parte la url en sus componentes
        $urlComponents = parse_url($urlEncoded);
        $urlPartToSign = $urlComponents["path"] . "?" . $urlComponents["query"];
        // Decode the private key into its binary format
        $binDecodedCryptokey = $this->decodeBase64UrlSafe($cryptokey);
        // Create a signature using the private key and the URL-encoded
        // string using HMAC SHA1. This signature will be binary.
        $signature = hash_hmac("sha1", $urlPartToSign, $binDecodedCryptokey, true);
        $encodedSignature = $this->encodeBase64UrlSafe($signature);
        return $encodedSignature;
    }

    /**
     * Crea una url codificada con la firma que le corresponde a la cryptokey
     * @param string $sUrlEncoded tipo: http://maps.google.com/maps/api/geocode/json?address=New+York&sensor=false&client=clientID
     * @param string $sCryptokey vNIXE0xscrmjlyV-12Nj_BvUPaw
     * @return string http://maps.google.com/maps/api/geocode/json?address=New+York&sensor=false&client=clientID&signature=xxxsignaturexxx
     */
    public function getUrlSigned($urlEncoded, $cryptokey)
    {
        $encodedSignature = $this->getEncodedSignature($urlEncoded, $cryptokey);
        return $urlEncoded."&signature=".$encodedSignature;
    }

    /**
     * Si se aplica la firma devuelve la url + client + signature
     * sino se asume que la url lleva client, con esta se genera una firma y se devuelve la url pasada + signature
     * @param string $sUrlEncoded Al menos lleva el parametro ?sensor=false. No debe llevar client=Client_ID
     * @return string url con tipo: http://..?sensor=false..&signature=encodedsignature
     */
    private function getUrlByKeypriority($urlEncoded)
    {
        //bug($sUrlEncoded,"url a firmar");
        if(!empty($urlEncoded)) {
            //Si se proporciona la firma y se ha especificado su uso no se evalua ninguna otra condición
            if(!empty($this->_sSignature) && $this->_useSignature) {
                $urlEncoded .= "&client=$this->_sClientId&signature=$this->_sSignature";
            }
            //En segundo nivel se comprueba la criptokey (pago plan empresarial)
            elseif(!empty($this->_sCriptokey) && $this->_useCriptoKey) {
                $this->_sSignature = $this->getEncodedSignature($urlEncoded, $this->_sCriptokey);
                $urlEncoded .= "&signature=$this->_sSignature";
            }
            //Despues se verifica la apikey (gratuita)
            elseif(!empty($this->_sApikey) && $this->_useApikey) {
                $urlEncoded .= "&key=$this->_sApikey";
            }
        }
        return $urlEncoded;
    }

    /**
     * Imprime en pantalla la url con la apikey.
     * Si se desea firmar hay que pasar la criptokey habilitar el objeto para su uso con use_criptokey
     * @param string $sUrlEncoded url con el clientid si se quiere imprimir con firma
     */
    public function showUrlByKeypriority($urlEncoded)
    {
        echo $this->getUrlByKeypriority($urlEncoded);
    }

    public function showGoogleJqueryTag()
    {
        echo $this->getGoogleJquery();
    }

    public function showApikeyTag()
    {
        echo $this->getApikeyTag();
    }

    //echo $this->build_url_signed("http://maps.google.com/maps/api/geocode/json?address=New+York&sensor=false&client=clientID","vNIXE0xscrmjlyV-12Nj_BvUPaw=");
    //======================
    //       GETTERS
    //======================
    public function getMarkers()
    {
        return $this->_arMarkers;
    }
    public function isMakersWithNumbers()
    {
        if($this->_useMakersNumbers) {
            echo "true";
        } else {
            echo "false";
        }
    }

    public function doDrawLines()
    {
        if($this->_drawLines) {
            echo "true";
        } else {
            echo "false";
        }
    }

    private function doDrawRoutes()
    {
        if($this->_drawRoutes) {
            echo "true";
        } else {
            echo "false";
        }
    }

    /**
     * Segun un array con datos de una dirección se recupera su latitud y longitud
     * @param array $arAddress array("pais"=>"España","direccion"=>"Conde de Peñalver 32", "zona"=>"Madrid", "cp"=>"28006");
     */
    public function getLatlongFromAddress(array $address)
    {
        //bug("get latlong from addres"); die;
        $ll = array("latitude" => "","longitude" => "");

        if(!empty($address)) {
            $urlApiGeocode = $this->_sUrlApiGeocode;
            //Une con comas el array
            $addrForUrl = join(", ", $address);
            //codifica el string en utf
            $addrForUrl = utf8_encode($addrForUrl);
            //codifica el string en formato url
            $addrForUrl = urldecode($addrForUrl);
            //sustituye espacios por el caracter +
            $addrForUrl = str_replace(" ", "+", $addrForUrl);
            //TODO se deberia adaptar esta url para usar la apikey privada o la firma única (signature)
            $urlApiGeocode = $urlApiGeocode."?address=".$addrForUrl."&sensor=false";
            //bug($sUrlApiGeocode);
            $xml = simplexml_load_file($urlApiGeocode);
            //bug($oXml); die;

            //Dependiendo de cuanto esté ocupado el servidor de google puede tardar mas ó menos.
            //Para asegurarnos que se cree correctamente el objeto xml con los detalles de la dirección pedida
            //forzamos a que el archivo espere unos instantes
            if($this->_useDelay) {
                usleep($this->_iDelayTime);
            }//microsegundos

            if($xml != false) {
                //bug($oXml,"oXml get_latlong_from_address");
                $xmlStatus = $xml->status;

                //status ok. Se ha localizado la dirección
                if(strcmp($xmlStatus, "OK") == 0) {
                    $latitude = $xml->result->geometry->location->lat;//v3
                    $longitude = $xml->result->geometry->location->lng;//v3
                    $latitude = (float) $latitude;
                    $longitude = (float) $longitude;
                    //bug($fLatitude,"latitud X"); bug($fLongitude,"longitud Y");
                    if($this->_doNarrowSearch) {
                        if($this->isInRangeLatlong($latitude, $longitude)) {
                            $ll["latitude"] = $latitude;
                            $ll["longitude"] = $longitude;
                            $this->_message = "Dirección encontrada";
                        }
                        //fuera de rango
                        else {
                            //bug("fuera de rango");
                            $this->setMessageError("Dirección fuera de rango: Lat:$latitude, Long:$longitude");
                        }
                    }
                    //No se usa acotación
                    else {
                        $ll["latitude"] = $latitude;
                        $ll["longitude"] = $longitude;
                        $this->_message = "Dirección encontrada";
                    }
                }
                //status de xml fallido
                else {
                    $this->setMessageError("La dirección $addrForUrl no se pudo geolocalizar. Estado=$xmlStatus");
                }
            }
            //error en simplexml_load_file
            else {
                $this->setMessageError("No se ha podido crear xml desde: $urlApiGeocode");
            }

        }
        return $ll;
    }

    /**
     * Determina si las coordenadas obtenidas de una dirección está dentro de los limites
     * de acotación
     * @param float $fLatitude
     * @param float $fLongitude
     * @return boolean
     */
    private function isInRangeLatlong($latitude = 0.0, $longitude = 0.0)
    {
        //bug($this->_arNarrowLat,"lat"); bug($fLatitude,"lat"); bug($this->_arNarrowLong,"long"); bug($fLongitude,"long");
        //bug($this->compare_float($fLatitude,"<",$this->_arNarrowLat["max"]),"lat < latmax?");
        //bug($this->compare_float($this->_arNarrowLat["min"],"<",$fLatitude),"latmin > latitude?");
        //bug($this->compare_float($fLongitude,"<",$this->_arNarrowLong["max"]),"long < longmax?");
        //bug($this->compare_float($this->_arNarrowLong["min"],"<",$fLongitude),"longmin > longitude?");
        $isInRange =
        (
            $this->compareFloat($latitude, "<", $this->_arNarrowLat["max"]) &&
            $this->compareFloat($this->_arNarrowLat["min"], "<", $latitude) &&

            $this->compareFloat($longitude, "<", $this->_arNarrowLong["max"]) &&
            $this->compareFloat($this->_arNarrowLong["min"], "<", $longitude)
        );
        return $isInRange;
    }

    /**
     * Función para comparar números en coma flotante (eaf)
     * La comparación de números en coma flotante no es tribial. No se puede
     * utilizar por ejemplo: $float1>$float2... es decir, los operadores comunes.
     * Esta función pasa los numeros a binarios con una precision marcada
     * @param float $float1
     * @param char $cOperator =, <, >, <=, >= !=
     * @param float $float2
     * @param integer $iPrecision
     * @return boolean o "operator error";
     */
    private function compareFloat($float1, $operator = "=", $float2, $precision = 10)
    {
        //Siempre la comparación se hace de float1 con respecto a float1
        // si el resultado es: 0 son igulaes, -1 f1 menor, 1 f1 mayor
        switch (trim($operator)) {
            case "=":
                return bccomp($float1, $float2, $precision) == 0;
                break;
            case "<":
                return bccomp($float1, $float2, $precision) == -1;
                break;
            case ">":
                return bccomp($float1, $float2, $precision) == 1;
                break;
            case "!=":
                return !(compare_float($float1, "=", $float2, $precision));
                break;
            case ">=":
                return compare_float($float1, ">", $float2, $precision)
                    || compare_float($float1, "=", $float2, $precision);
                break;
            case "<=":
                return compare_float($float1, "<", $float2, $precision)
                    || compare_float($float1, "=", $float2, $precision);
                break;

            default:
                return "operator error";
                break;
        }
    }

    /*
     * Devuelve el string con tag script haciendo referencia al archivo jquery del repositorio de
     * google
     */
    public function getGoogleJquery()
    {
        $tagJquery = "<script type=\"text/javascript\" src=\"https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js\"></script>\n";
        return $tagJquery;
    }

    public function getApikeyTag()
    {
        $apiUrl = "<script type=\"text/javascript\" src=\"noapikeysuplied\"></script>";
        //bug($this->_sApikey);
        if(!empty($this->_sApikey)) {
            //http://maps.googleapis.com/maps/api/js?v3&key=YOUR_API_KEY&sensor=TRUE_OR_FALSE&callback=initialize
            $apiUrl = "<script type=\"text/javascript\" src=\"http://maps.googleapis.com/maps/api/js?v=3&sensor=false\"></script>\n";
        }
        return $apiUrl;
    }

    public function getJsapiV3Tag()
    {
        //https://developers.google.com/maps/documentation/business/guide#Accessing
        //url demo:   http://maps.googleapis.com/maps/api/js?v=3&client=gme-yourclientid&sensor=true_or_false&channel=yourchannel
        $urlJsApi = "http://maps.googleapis.com/maps/api/js?v=3&sensor=false";
        if(!empty($this->_sClientId)) {
            $urlJsApi .= "&client=$this->_sClientId";
        }
        if(!empty($this->_sChannel)) {
            $urlJsApi .= "&channel=$this->_sChannel";
        }
        //no es necesario usar firma
        //$sUrlJsApi = $this->get_url_by_keypriority($sUrlJsApi);
        $keyTag = "<script type=\"text/javascript\" src=\"$urlJsApi\"></script>\n";
        return $keyTag;
    }

    public function showJsapiV3Tag()
    {
        echo $this->getJsapiV3Tag();
    }

    private function getMaptype()
    {
        echo $this->_sMapType;
    }
    private function getDivContainer()
    {
        echo $this->_sIdDivContainer;
    }
    private function getZoom()
    {
        echo $this->_iZoom;
    }
    private function getLatitude()
    {
        echo $this->_fLatitude;
    }
    private function getLongitude()
    {
        echo $this->_fLongitude;
    }
    private function getHeight()
    {
        echo $this->_iHeight;
    }
    private function getWidth()
    {
        echo $this->_iWidth;
    }
    private function getSizeUnit()
    {
        echo "'$this->_sUnitWH'";
    }
    private function getRouteType()
    {
        echo "'$this->_sRouteMode'";
    }
    private function getRouteColor()
    {
        echo "'$this->_sRouteColor'";
    }
    private function getRouteWidth()
    {
        echo $this->_iRouteWidth;
    }
    private function getRouteAlpha()
    {
        echo $this->_fRouteAlpha;
    }
    private function getMarkerColor()
    {
        echo "'$this->_sMarkerColor'";
    }

    public function getMessage()
    {
        return $this->_message;
    }
    public function getApikey()
    {
        return $this->_sApikey;
    }
    public function getSignature()
    {
        return $this->_sSignature;
    }


    //======================
    //       SETTERS
    //======================

    //TODO Corregir en el futuro
    public function setMarkers($markers = array())
    {
        $this->_arMarkers = $markers;
    }
    public function setMarkersNumbersOff($isOn = false)
    {
        $this->_useMakersNumbers = $isOn;
    }

    public function setMaptype($value)
    {
        $this->_sMapType = strtolower("'$value'");
    }
    public function setDivContainer($value)
    {
        $this->_sIdDivContainer = "'$value'";
    }
    public function setZoom($zoom)
    {
        $this->_iZoom = $zoom;
    }
    public function setLatitude($latitude)
    {
        $this->_fLatitude = $latitude;
    }
    public function setLongitude($longitude)
    {
        $this->_fLongitude = $longitude;
    }
    public function drawLines($isOn = true)
    {
        $this->_drawLines = $isOn;
    }
    public function setSizeContainer($width = 800, $height = 600)
    {
        if(!empty($height)) {
            $this->_iHeight = $height;
        }
        if(!empty($width)) {
            $this->_iWidth = $width;
        }
    }
    public function setSizeUnit($type = "pt")
    {
        $this->_sUnitWH = $type;
    }
    public function addAddress(array $address)
    {
        $this->_arAddresses[] = $address;
    }
    public function drawRoutes($isOn = true)
    {
        $this->_drawRoutes = $isOn;
    }
    public function setRouteColor($color = "green")
    {
        $this->_sRouteColor = $color;
    }
    public function setMarkerColor($color = "green")
    {
        $this->_sMarkerColor = $color;
    }

    /**
     * @param string $sType driving, walking, bicycling
     */
    public function setRouteType($type = "driving")
    {
        $this->_sRouteMode = $type;
    }

    public function setRouteWidth($width = 3)
    {
        $this->_iRouteWidth = $width;
    }
    public function setRouteAlpha($alpha = 0.5)
    {
        $this->_fRouteAlpha = $alpha;
    }

    private function setMessageError($message, $isError = true)
    {
        $this->_is_error = $isError;
        $this->_message = $message;
    }
    /**
     * Solo influye para recuperar latitud y longitud de direcciones.
     * No en el pintado de marcadores
     * @param boolean $isOn
     */
    public function noNarrow($isOn = false)
    {
        $this->_doNarrowSearch = $isOn;
    }
    public function setDelayTime($microSeconds)
    {
        $this->_iDelayTime = $microSeconds;
    }
    public function noDalay($isOn = false)
    {
        $this->_useDelay = $isOn;
    }
    public function setApikey($apikey)
    {
        $this->_sApikey = $apikey;
    }
    public function noAutoApikey($isOn = false)
    {
        $this->_useApikey = $isOn;
    }
    public function noGoogleJquery($isOn = false)
    {
        $this->_useGoogleJquery = $isOn;
    }
    public function setSignature($value)
    {
        $this->_sSignature = $value;
    }
    public function useSignature($isOn = true)
    {
        $this->_useSignature = $isOn;
    }
    public function setCryptokey($value)
    {
        $this->_sCriptokey = $value;
    }
    public function useCryptokey($isOn = true)
    {
        $this->_useCriptoKey = $isOn;
    }
    public function setClientId($id)
    {
        $this->_sClientId = $id;
    }
    public function setChannel($channelJs)
    {
        $this->_sChannel = $channelJs;
    }
}
