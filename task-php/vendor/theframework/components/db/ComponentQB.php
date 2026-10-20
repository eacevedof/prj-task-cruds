<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @name TheFramework\Components\Db\ComponentQB
 * @file ComponentQB.php 3.0.0
 * @date 10-01-2022 20:31 SPAIN
 * @observations
 */

namespace TheFramework\Components\Db;

use Exception;

final class ComponentQB
{
    private string $comment         = "";
    private string $table           = ""; //Tabla sobre la que se realizará el crud
    private bool $isdistinct        = false;
    private bool $calcfoundrows     = false;
    private array $argetfields      = [];
    private array $arjoins          = [];
    private array $arands           = [];
    private array $argroupby        = [];
    private array $arhaving         = [];
    private array $arorderby        = [];
    private array $arlimit          = [];
    private array $arend            = [];

    private array $arnumeric        = []; //si esta en este array no se escapa con '
    private array $arinsertfv       = [];

    private array $arupdatefv       = [];
    private array $arpks            = [];
    private array $select           = [];

    private string $sql             = "";
    private string $sqlCount        = "";

    private ?Object $db = null;

    private array $reserved = ["get", "order", "password"];

    public const READ = "r";
    public const WRITE = "w";

    public const ORDER_ASC = "ASC";
    public const ORDER_DESC = "DESC";

    // "campo" o "alias.campo": lo unico que se admite como nombre en un ORDER BY por identificador
    private const IDENTIFIER_PATTERN = "/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/";
    // el nombre/direccion invalidos llegan del request -> error del cliente, no del servidor
    private const CODE_BAD_REQUEST = 400;

    public function __construct(string $table = "")
    {
        $this->table = $table;
    }

    private function getJoins(): string
    {
        if (!$this->arjoins) {
            return "";
        }
        return implode("\n", $this->arjoins);
    }

    private function getHaving(): string
    {
        if (!$this->arhaving) {
            return "";
        }
        $arsql = [];
        foreach($this->arhaving as $havecond) {
            $arsql[] = $havecond;
        }
        return "HAVING ".implode(", ", $arsql);
    }

    private function getGroupby(): string
    {
        if (!$this->argroupby) {
            return "";
        }
        $arsql = [];
        foreach($this->argroupby as $field) {
            $this->cleanReserved($field);
            $arsql[] = $field;
        }
        return "GROUP BY ".implode(",", $arsql);
    }

    private function getOrderby(): string
    {
        if (!$this->arorderby) {
            return "";
        }
        $arsql = [];
        foreach($this->arorderby as $field => $AD) {
            $this->cleanReserved($field);
            $arsql[] = "$field $AD";
        }
        return "ORDER BY ".implode(",", $arsql);
    }

    private function getEnd(): string
    {
        if (!$this->arend) {
            return "";
        }
        return " ".implode("\n", $this->arend);
    }

    private function getLimit(): string
    {
        if (!$this->arlimit) {
            return "";
        }
        // LIMIT regfrom (secuenta desde 0), perpage

        if ($this->arlimit["regfrom"] === 0) {
            return " LIMIT {$this->arlimit["perpage"]}";
        }
        $limit = implode(", ", $this->arlimit);
        return " LIMIT $limit";
        /**
         * si por ejemplo deseo paginar de 10 en 10
         * para la pag:
         *  1 sería LIMIT 0,10   -- 1 a 10
         *  2 LIMIT 10,10        -- 11 a 20
         *  3 LIMIT 20,10        -- 21 a 30
         */
    }

    private function isNumeric(string $fieldname): bool
    {
        return in_array($fieldname, $this->arnumeric);
    }

    private function isReserved(string $word): bool
    {
        return in_array(strtolower($word), $this->reserved);
    }

    private function cleanReserved(&$mxfields): void
    {
        if (is_string($mxfields)) {
            if ($this->isReserved($mxfields)) {
                $mxfields = "`$mxfields`";
            }
            return;
        }

        if (is_array($mxfields)) {
            foreach ($mxfields as $i => $field) {
                if ($this->isReserved($field)) {
                    $mxfields[$i] = "`$field`";
                }
            }
        }
    }

    private function isTagged(string $value): bool
    {
        //$value = trim($value);
        $tagini = substr($value, 0, 2);
        $tagend = substr($value, -2);
        $ilen = strlen($value);
        if ($ilen > 4 && $tagini === "%%" && $tagend === "%%") {
            $field = substr($value, 2, $ilen - 4);
            return (trim($field) !== "");
        }
        return false;
    }

    private function getUntagged(string $tagged): string
    {
        $ilen = strlen($tagged);
        return substr($tagged, 2, $ilen - 4);
    }

    private function exception(string $message, int $code = 500): void
    {
        throw new Exception($message, $code);
    }

    private function getPkconds(array $arpks): array
    {
        $arconds = [];
        $arpks = array_unique($arpks);
        foreach($arpks as $field => $strval) {
            $this->cleanReserved($field);
            if ($strval === null) {
                $arconds[] = "$field IS NULL";
            } elseif ($this->isTagged($strval)) {
                $arconds[] = "$field={$this->getUntagged($strval)}";
            } elseif ($this->isNumeric($field)) {
                $arconds[] = "$field=$strval";
            } else {
                $arconds[] = "$field='$strval'";
            }
        }
        return $arconds;
    }

    public function insert(?string $table = null, ?array $arfieldval = null): self
    {
        $this->sql = "/*error insert*/";
        if (!$table) {
            $table = $this->table;
        }
        if (!$table) {
            $this->exception("missing table in insert");
        }

        $comment = $this->comment ? "/*$this->comment*/" : "/*insert*/";
        $arfieldval = $arfieldval ?? $this->arinsertfv;
        if (!$arfieldval) {
            $this->exception("missing fields and values in insert");
        }

        $sql = "$comment INSERT INTO ";
        $sql .= "$table ( ";

        $fields = array_keys($arfieldval);
        $this->cleanReserved($fields);
        $sql .= implode(",", $fields);

        $values = array_values($arfieldval);
        //los paso a entrecomillado
        foreach ($values as $strval) {
            $araux[] = $strval === null ? "null" : "'$strval'";
        }

        $sql .= ") VALUES (";
        $sql .= implode(",", $araux);
        $sql .= ")";

        $this->sql = $sql;
        //to-do
        //$this->query("w");
        return $this;
    }//insert

    public function update(?string $table = null, ?array $arfieldval = null, ?array $arpks = null): self
    {
        $this->sql = "/*error update*/";
        if (!$table) {
            $table = $this->table;
        }
        if (!$table) {
            $this->exception("missing table in update");
        }

        $comment = $this->comment ? "/*$this->comment*/" : "/*update*/";
        $arfieldval = $arfieldval ?? $this->arupdatefv;
        if (!$arfieldval) {
            $this->exception("missing fields and values in update");
        }
        $arpks = $arpks ?? $this->arpks;

        $sql = "$comment UPDATE $table ";
        $sql .= "SET ";
        //creo las asignaciones de campos set extras
        $arsets = [];
        foreach($arfieldval as $field => $strval) {
            $this->cleanReserved($field);
            if ($strval === null) {
                $arsets[] = "$field=null";
            } elseif ($this->isTagged($strval)) {
                $arsets[] = "$field={$this->getUntagged($strval)}";
            } elseif ($this->isNumeric($field)) {
                $arsets[] = "$field=$strval";
            } else {
                $arsets[] = "$field='$strval'";
            }
        }

        $sql .= implode(",", $arsets);
        $sql .= " WHERE 1 ";

        //condiciones con las claves
        $arconds = $this->getPkconds($arpks);

        $arconds = array_merge($arconds, $this->arands);
        if ($arconds) {
            $sql .= "AND ".implode(" AND ", $arconds);
        }

        $sql .= $this->getEnd();
        $this->sql = $sql;
        //si hay bd intenta ejecutar la consulta
        //$this->query("w");
        return $this;
    }//update

    public function delete(?string $table = null, ?array $arpks = null): self
    {
        $this->sql = "/*error delete*/";
        if (!$table) {
            $table = $this->table;
        }
        if (!$table) {
            $this->exception("missing table in delete");
        }

        $comment = $this->comment ? "/*$this->comment*/" : "/*delete*/";
        $arpks = $arpks ?? $this->arpks;

        $sql = "$comment DELETE FROM $table ";

        //condiciones con las claves
        $arconds = $this->getPkconds($arpks);

        $sql .= " WHERE 1 ";

        $araux = array_merge($arconds, $this->arands);
        if ($araux) {
            $sql .= "AND ".implode(" AND ", $araux);
        }

        $sql .= $this->getEnd();
        $this->sql = $sql;
        //si hay bd intenta ejecutar la consulta
        //$this->query("w");
        return $this;
    }//delete

    private function getSqlCount(): string
    {
        $arsql = $this->select;
        unset($arsql["orderby"], $arsql["limit"]);
        $subselect = trim(implode(" ", $arsql));
        $sql = [
            "SELECT",
            "COUNT(*) AS foundrows",
            "FROM (\n",
            $subselect,
            "\n) AS sqlcount"
        ];
        return implode(" ", $sql);
    }

    private function removeEmptyItem(array &$array): void
    {
        foreach ($array as $i => $item) {
            if ($item === "" || is_null($item)) {
                unset($array[$i]);
            }
        }
    }

    public function select(?string $table = null, ?array $fields = null, ?array $arpks = null): self
    {
        $this->sql = "/*error select*/";
        $this->sqlCount = "/*error selectcount*/";

        if (!$table) {
            $table = $this->table;
        }
        if (!$table) {
            $this->exception("missing table in select");
        }

        $fields = $fields ?? $this->argetfields;
        if (!$fields) {
            $this->exception("missing fields in select");
        }

        $comment = $this->comment ? "/*$this->comment*/" : "/*select*/";
        $arpks = $arpks ?? $this->arpks;

        $this->select[] = "$comment SELECT";
        if ($this->isdistinct) {
            $this->select[] = "DISTINCT";
        }
        $this->cleanReserved($fields);
        $this->select[] = implode(",", $fields);
        $this->select[] = "\nFROM $table";

        $this->select[] = $this->getJoins();

        $arconds = $this->getPkconds($arpks);
        $araux = array_merge($arconds, $this->arands);
        if ($araux) {
            $this->select[] = "\nWHERE ".implode(" AND ", $araux);
        }

        $this->select[] = $this->getGroupby();
        $this->select[] = $this->getHaving();
        $this->select["orderby"] = $this->getOrderby();
        $this->select[] = $this->getEnd();
        $this->select["limit"] = $this->getLimit();

        $this->removeEmptyItem($this->select);

        $this->sql = implode(" ", $this->select);
        $this->sqlCount = "";
        if ($this->calcfoundrows) {
            $this->sqlCount = $this->getSqlCount();
        }
        return $this;
    }//get_selectfrom

    public function setTable(string $table): self
    {
        $this->table = $table;
        return $this;
    }
    public function setComment(string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function setInsertFv(array $arfieldval = []): self
    {
        $this->arinsertfv = [];
        if (is_array($arfieldval)) {
            $this->arinsertfv = $arfieldval;
        } return $this;
    }
    public function addInsertFv(string $fieldname, $strval, bool $dosanit = true): self
    {
        $this->arinsertfv[$fieldname] = ($dosanit) ? $this->getSanitizedString($strval) : $strval;
        return $this;
    }

    public function setPksFv(array $arfieldval = []): self
    {
        $this->arpks = [];
        if (is_array($arfieldval)) {
            $this->arpks = $arfieldval;
        } return $this;
    }
    public function addPkFv(string $fieldname, $strval, bool $dosanit = true): self
    {
        $this->arpks[$fieldname] = ($dosanit) ? $this->getSanitizedString($strval) : $strval;
        return $this;
    }

    public function setUpdateFv(array $arfieldval = []): self
    {
        $this->arupdatefv = [];
        if (is_array($arfieldval)) {
            $this->arupdatefv = $arfieldval;
        } return $this;
    }
    public function addUpdateFv(string $fieldname, $strval, bool $dosanit = true): self
    {
        $this->arupdatefv[$fieldname] = ($dosanit) ? $this->getSanitizedString($strval) : $strval;
        return $this;
    }

    public function setGetfields(array $fields = []): self
    {
        $this->argetfields = [];
        if (is_array($fields)) {
            $this->argetfields = $fields;
        } return $this;
    }
    public function addGetfield(string $fieldname): self
    {
        $this->argetfields[] = $fieldname;
        return $this;
    }

    public function setJoins(array $arjoins = []): self
    {
        $this->arjoins = [];
        if (is_array($arjoins)) {
            $this->arjoins = $arjoins;
        } return $this;
    }
    /**
     * Sustituye el ORDER BY. Cada clave es un IDENTIFICADOR (campo o alias.campo) y cada valor
     * ASC/DESC: se validan igual que en addOrderby(). Para expresiones: addOrderbyExpression().
     */
    public function setOrderby(array $arorderby = []): self
    {
        $this->arorderby = [];
        foreach ($arorderby as $fieldname => $order) {
            $this->addOrderby($fieldname, $order);
        }
        return $this;
    }
    public function setGroupby(array $argroupby = []): self
    {
        $this->argroupby = [];
        if (is_array($argroupby)) {
            $this->argroupby = $argroupby;
        } return $this;
    }
    public function setHaving(array $arhaving = []): self
    {
        $this->arhaving = [];
        if (is_array($arhaving)) {
            $this->arhaving = $arhaving;
        } return $this;
    }

    public function setEnd(array $arend = []): self
    {
        $this->arend = [];
        if (is_array($arend)) {
            $this->arend = $arend;
        } return $this;
    }
    public function setLimit(?int $ppage = 1000, int $regfrom = 0): self
    {
        $this->arlimit = ["regfrom" => $regfrom, "perpage" => $ppage];
        if ($ppage == null) {
            $this->arlimit = [];
        }
        return $this;
    }

    public function sql(): string
    {
        return $this->sql;
    }
    public function sqlcount(): string
    {
        return $this->sqlCount;
    }

    public function getSanitizedString(?string $strval): ?string
    {
        if ($strval === null) {
            return null;
        }
        // no se pq he escapado el % y el _ pero no debería
        //$strval = str_replace("\'","",$strval);
        //$strval = stripslashes($strval);
        $strval = str_replace("\\", "\\\\", $strval);
        $strval = str_replace("'", "\'", $strval);
        //$strfixed = str_replace("%","\%",$strfixed);
        //$strfixed = str_replace("_","\_",$strfixed); si quiero guardar  SQL_CALC_FOUND_ROWS me hace SQL\_CALC_\
        return $strval;
    }//get_sanitized

    /**
     * @param char $mode READ para selects, WRITE update,insert,delete
     * @return mixto
     */
    public function exec(string $mode = self::READ)
    {
        $result = [];
        if (!$this->db) {
            $this->exception("no db object not configured for get_result");
        }
        if (!$this->sql) {
            $this->exception("empty sql in get_result");
        }
        if (!in_array($mode, [self::READ, self::WRITE])) {
            $this->exception("unrecognized mode");
        }

        //insert,update,delete
        if (method_exists($this->db, "exec") && $mode == self::WRITE) {
            return $this->db->exec($this->sql);
        }

        if (method_exists($this->db, "query") && $mode == self::READ) {
            return $this->db->query($this->sql);
        }

        $this->exception("missing exec or query method in db object");
    }//get_result

    public function setDb(Object $db): self
    {
        $this->db = $db;
        return $this;
    }

    public function enableDistinct(): self
    {
        $this->isdistinct = true;
        return $this;
    }

    public function disableDistinct(): self
    {
        $this->isdistinct = false;
        return $this;
    }
    public function enableCalcFoundRows(): self
    {
        $this->calcfoundrows = true;
        return $this;
    }

    public function disableCalcFoundRows(): self
    {
        $this->calcfoundrows = false;
        return $this;
    }
    public function addNumeric(string $fieldname): self
    {
        $this->arnumeric[] = $fieldname;
        return $this;
    }
    public function setAnd(array $arands = []): self
    {
        $this->arands = [];
        if (is_array($arands)) {
            $this->arands = $arands;
        } return $this;
    }
    public function addAnd(string $condition): self
    {
        $this->arands[] = $condition;
        return $this;
    }
    public function addAndoper(string $fieldname, $strval, string $oper = "="): self
    {
        $this->arands[] = "$fieldname $oper $strval";
        return $this;
    }
    public function addIn(string $fieldname, array $values): self
    {
        $isnum = in_array($fieldname, $this->arnumeric);
        $values = array_unique($values);
        $glue = $isnum ? "," : "','";
        $in = implode($glue, $values);
        $in = $isnum ? "($in)" : "('$in')";
        $this->arands[] = "$fieldname IN $in";
        return $this;
    }

    public function addJoin(string $sjoin, ?string $key = null): self
    {
        if ($key) {
            $this->arjoins[$key] = $sjoin;
        } else {
            $this->arjoins[] = $sjoin;
        } return $this;
    }
    /**
     * ORDER BY por IDENTIFICADOR: solo "campo" o "alias.campo". Cualquier otra cosa (comas,
     * parentesis, espacios, subconsultas) lanza excepcion: el nombre puede venir del request
     * (columns[i][data] de los datatables) y aqui acababa pegado al SQL sin tocar.
     */
    public function addOrderby(string $fieldname, string $order = "ASC"): self
    {
        if (!preg_match(self::IDENTIFIER_PATTERN, $fieldname)) {
            $this->exception("invalid order by field: {$fieldname}", self::CODE_BAD_REQUEST);
        }
        $this->arorderby[$fieldname] = $this->getValidatedOrderDirection($order);
        return $this;
    }

    /**
     * Nombre de campo que llega del request (columns[i][data] de los datatables) y que se va a pegar
     * a una condicion escrita a mano (LIKE de las busquedas): solo "campo" o "alias.campo".
     */
    public function getValidatedIdentifier(string $fieldname): string
    {
        if (!preg_match(self::IDENTIFIER_PATTERN, $fieldname)) {
            $this->exception("invalid field: {$fieldname}", self::CODE_BAD_REQUEST);
        }
        return $fieldname;
    }

    /**
     * ORDER BY por EXPRESION escrita en el codigo (COALESCE, CASE…). Nunca pasarle nada que venga
     * del request: la expresion no se valida, solo la direccion.
     */
    public function addOrderbyExpression(string $expression, string $order = "ASC"): self
    {
        $this->arorderby[$expression] = $this->getValidatedOrderDirection($order);
        return $this;
    }

    private function getValidatedOrderDirection(string $order): string
    {
        $order = strtoupper(trim($order));
        if (!in_array($order, [self::ORDER_ASC, self::ORDER_DESC], true)) {
            $this->exception("invalid order by direction: {$order}", self::CODE_BAD_REQUEST);
        }
        return $order;
    }
    public function addGroupby(string $fieldname): self
    {
        $this->argroupby[] = $fieldname;
        return $this;
    }
    public function addHaving(string $having): self
    {
        $this->arhaving[] = $having;
        return $this;
    }
    public function addEnd(string $strend, ?string $key = null): self
    {
        if ($key) {
            $this->arend[$key] = $strend;
        } else {
            $this->arend[] = $strend;
        } return $this;
    }
}//Crud 3.0.0
