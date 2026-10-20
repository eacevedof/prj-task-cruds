<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 2.1.0
 * @name ComponentCookie
 * @file ComponentCookie.php
 * @date 02-11-2021 21:58 (SPAIN)
 * @observations: Usa las funciones básicas de php para tratar cookies
 * La cookie realmente es el par (clave,valor) que se guarda y tiene un tiempo de caducidad (expire)
 * El archivo como tal que guarda estos valores no es la cookie!
 */

namespace TheFramework\Components\Session;

final class ComponentCookie
{
    private $_name;
    private $_value;
    private $_expire;
    private $_valid_path;
    private $_domain;
    private $isSecured;
    private $isHttpOnly;

    //Variables de control
    private $_message = array();
    private $_is_error = false;

    public function __construct($name = "", $value = "", $valid_path = "/", $expire_time = 0, $domain = "", $issecured = false, $httponly = false)
    {
        $this->_name = $name;
        $this->_value = $value;
        $this->_valid_path = $valid_path;
        $this->_expire = time() + 60 * 60 * 24 * 30;//caduca en 30 días
        if ($expire_time) {
            $this->_expire = $expire_time;
        }
        $this->_domain = $domain;
        $this->isSecured = $issecured;
        $this->isHttpOnly = $httponly;
    }

    public function kill($name = null)
    {
        if ($name) {
            $this->_name = $name;
        }
        //setcookie suele dar un error si se hace un echo o algun print por pantalla antes de llamar a esta función
        $this->_is_error = setcookie($this->_name, "", time() - 3600, $this->_valid_path, $this->_domain, $this->isSecured, $this->isHttpOnly);//
        unset($_COOKIE[$this->_name]);
        return $this;
    }

    public function killall()
    {
        if (isset($_SERVER["HTTP_COOKIE"])) {
            $cookies = explode(";", $_SERVER["HTTP_COOKIE"]);
            foreach($cookies as $keyVal) {
                $parts = explode("=", $keyVal);
                $name = trim($parts[0]);
                setcookie($name, "", time() - 1000);
                setcookie($name, "", time() - 1000, "/");
            }
        }
        return $this;
    }//killall

    public function write($name = null, $value = null): self
    {
        if ($name) {
            $this->_name = $name;
        }
        if ($value) {
            $this->_value = $value;
        }

        //setcookie devuelve true si todo va bien
        $this->_is_error = !setcookie(
            $this->_name,
            $this->_value,
            $this->_expire,
            $this->_valid_path,
            $this->_domain,
            $this->isSecured,
            $this->isHttpOnly
        );

        return $this;
    }

    private function loadValue($name = null)
    {
        if ($name) {
            $this->_name = $name;
        }
        $this->_value = (isset($_COOKIE[$this->_name]) ? $_COOKIE[$this->_name] : null);
        return $this;
    }

    public function setName($value)
    {
        $this->_name = $value;
        return $this;
    }
    public function setValue($value)
    {
        $this->_value = $value;
        $this->write();
        return $this;
    }

    public function addValue($name, $value)
    {
        $this->_name = $name;
        $this->_value = $value;
        if ($this->_name) {
            $this->write();
        }
        return $this;
    }

    public function setExpire($value)
    {
        $this->_expire = $value;
        return $this;
    }
    public function setValidPath($value)
    {
        $this->_valid_path = $value;
        return $this;
    }
    public function setDomain($value)
    {
        $this->_domain = $value;
        return $this;
    }
    public function setSecured($isOn = true)
    {
        $this->isSecured = $isOn;
        return $this;
    }
    public function setHttponly($isOn = true)
    {
        $this->isHttpOnly = $isOn;
        return $this;
    }

    public function getName()
    {
        return $this->_name;
    }
    public function getValue($name = null)
    {
        $this->loadValue($name);
        return $this->_value;
    }
    public function getExpire()
    {
        return $this->_expire;
    }
    public function getValidPath()
    {
        return $this->_valid_path;
    }
    public function getDomain()
    {
        return $this->_domain;
    }
    public function getSecured()
    {
        return $this->isSecured;
    }
    public function getHttponly()
    {
        return $this->isHttpOnly;
    }
    public function getAll()
    {
        return $_COOKIE;
    }
    public function exists($name = null)
    {
        if ($name) {
            $this->_name = $name;
        } return key_exists($this->_name, $_COOKIE);
    }
    public function getArray($name)
    {
        $cookie = array();
        if (isset($_COOKIE[$name])) {
            foreach($_COOKIE[$name] as $key => $value) {
                $cookie[$key] = $value;
            }
        }
        return $cookie;
    }
    public function isError()
    {
        return $this->_is_error;
    }

}//ComponentCookie
