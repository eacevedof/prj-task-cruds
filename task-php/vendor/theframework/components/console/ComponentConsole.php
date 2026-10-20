<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @name ComponentConsole
 * @file ComponentConsole.php
 * @version 1.0.0
 * @date 03-03-2017 15:40
 * @observations
 */

namespace TheFramework\Components\Console;

class ComponentConsole
{
    private $pathLogs;
    private $pathClass;
    private $className;
    private $method;
    private $arguments;

    private $isError;
    private $errors;

    public function __construct($argv)
    {
        $this->setArguments($argv);
        $this->pathLogs = realpath(dirname(__FILE__));
        //$this->debug($argv);die;
    }

    private function getNamespace($pathClass)
    {
        $content = file_get_contents($pathClass);
        $lines = explode("\n", $content);
        foreach($lines as $line) {
            if (strstr($line, "namespace")) {
                $nameSpace = str_replace("namespace", "", $line);
                $nameSpace = str_replace(";", "", $nameSpace);
                $nameSpace = trim($nameSpace);
                return $nameSpace;
            }
        }

        //die("namespace $sNameSpace");
        return "";
    }

    public function run()
    {
        try {
            if (is_file($this->pathClass)) {
                include_once $this->pathClass;
                $incFiles = get_included_files();

                if (in_array($this->pathClass, $incFiles)) {
                    $nameSpace = $this->getNamespace($this->pathClass);
                    $fullClass = "\\$this->className";
                    if ($nameSpace) {
                        $fullClass = "\\$nameSpace\\$this->className";
                    }
                    //die("fullclass:$sFullClass");
                    if (class_exists($fullClass)) {
                        if ($this->arguments) {
                            $object = new $fullClass($this->arguments);
                        } else {
                            $object = new $fullClass();
                        }

                        if (is_object($object)) {
                            if (method_exists($object, $this->method)) {
                                $object->{$this->method}();
                            } else {
                                $this->addError("Method does not exist: $this->method in $fullClass");
                                $this->log("Method does not exist: $this->method in $fullClass");
                            }
                        } else {
                            $this->addError("Not an object:  \$oObject");
                            $this->log("Not an object:  \$oObject");
                        }
                    }
                    //clase no encontrada
                    else {
                        $this->addError("Class not found: $this->className");
                        $this->log("Class not found: $this->className");
                    }
                }
                //no se ha incluido
                else {
                    $this->addError("File not included: $this->pathClass");
                    $this->log("File not included: $this->pathClass");
                }
            }
            //no existe
            else {
                $this->addError("File does not exist: $this->pathClass");
                $this->log("File does not exist: $this->pathClass");
            }
        } catch (Exeption $e) {
            $this->addError("Exception: {$e->getMessage()}");
            $this->log("Exception: {$e->getMessage()}");
        }

        if ($this->isError) {
            $this->showErrors();
        }
    }//run()

    private function log($content, $title = "")
    {
        if (!is_string($content)) {
            $content = var_export($content, 1);
        }
        $this->save($content, $title);
    }

    private function merge($content, $title)
    {
        $return = "::".date("Ymd-His")."::\n";
        if ($title) {
            $return .= $title.":\n";
        }
        if ($content) {
            $return .= $content."\n\n";
        }
        return $return;
    }

    private function save($content, $title = null)
    {
        $today = date("Ymd");
        $pathFile = $this->pathLogs."/console_$today.log";
        if (is_file($pathFile)) {
            $cursor = fopen($pathFile, "a");
        } else {
            $cursor = fopen($pathFile, "x");
        }

        if ($cursor !== false) {
            $toSave = $this->merge($content, $title);
            fwrite($cursor, ""); //Grabo el caracter vacio
            if (!empty($toSave)) {
                fwrite($cursor, $toSave);
            }
            fclose($cursor); //cierro el archivo.
        } else {
            return false;
        }
        return true;
    }

    public function debug($var)
    {
        echo "<pre>".var_export($var, 1);
    }

    private function addError($message)
    {
        $this->isError = true;
        $this->errors[] = $message;
    }
    public function isError()
    {
        return $this->isError;
    }
    public function getErrors()
    {
        return $this->errors;
    }
    public function showErrors()
    {
        echo "<pre>".var_export($this->errors, 1);
    }

    public function setPathclass($path)
    {
        $this->pathClass = $path;
    }
    public function setClassname($className)
    {
        $this->className = $className;
    }
    public function setMethod($method)
    {
        $this->method = $method;
    }
    public function setArguments($argv)
    {
        $this->arguments = $argv;
        unset($this->arguments[0]);//run.php
        unset($this->arguments[1]);//path_class
        unset($this->arguments[2]);//classname
        unset($this->arguments[3]);//method
    }

}//ComponentConsole
