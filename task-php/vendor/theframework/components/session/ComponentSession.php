<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.3.1
 * @name ComponentSession
 * @file ComponentSession.php
 * @date 23-04-2017 12:37 (SPAIN)
 * @observations
 *  load:27
 */
class ComponentSession extends TheFrameworkComponent
{
    public function __construct()
    {
        ;
    }

    public function close()
    {
        if (!headers_sent()) {
            session_unset();
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION = null; //$_GET=NULL; $_POST=NULL;
            /*CLOSE PREVIOUS SESSION*/
            //session_unlink();
            session_destroy();
            /*NOW GENERATING LINK FOR SESSION DATA */
            session_id();
            session_start();
            session_write_close();
            //session_regenerate_id();//Regenerating SID for sending
        }
    }

    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }
    public function setFilter($controller, $partial, $method, $value)
    {
        $key = "$controller$partial$method";
        $_SESSION[$key] = $value;
    }

    public function clear($key)
    {
        unset($_SESSION[$key]);
    }
    public function clearall()
    {
        unset($_SESSION);
    }
    /**
     * Escribe en $_SESSION["tfw_user_identificator"] = $iId
     * @param int $iId Puede ser negativo
     */
    public function setUserId($id)
    {
        $_SESSION["tfw_user_identificator"] = $id;
    }
    public function setUserLanguage($language)
    {
        $_SESSION["tfw_user_language"] = $language;
    }
    public function setUserLanguageId($IdLanguage)
    {
        $_SESSION["tfw_user_idlanguage"] = $IdLanguage;
    }

    public function get($key)
    {
        return $_SESSION[$key];
    }
    public function getFilter($controller, $partial, $method)
    {
        $key = "$controller$partial$method";
        return $this->get($key);
    }

    public function exists($key)
    {
        return ($_SESSION[$key] != null);
    }

    public function getUserId()
    {
        return isset($_SESSION["tfw_user_identificator"]) ? $_SESSION["tfw_user_identificator"] : null;
    }
    public function getUserLanguage()
    {
        return isset($_SESSION["tfw_user_language"]) ? $_SESSION["tfw_user_language"] : null;
    }
    public function getUserIdLanguage()
    {
        return isset($_SESSION["tfw_user_idlanguage"]) ? $_SESSION["tfw_user_idlanguage"] : null;
    }
    public function getId()
    {
        return session_id();
    }

}//ComponentSession
