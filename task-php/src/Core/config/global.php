<?php
declare(strict_types=1);

use App\Modules\Shared\Domain\Enums\AppEnvKeyEnum;
use App\Modules\Shared\Infrastructure\Components\Logger\Logger;

use App\Core\Boot\BootPathsEnum;

/**
 * Carga backend_web/.env y FALLA RUIDOSO si tras cargarlo falta alguna clave obligatoria. Sin .env la app
 * arrancaba degradada en silencio y el fallo se disfrazaba de error de dominio/BD/vault
 * ("Domain  is not authorized"). Las vars REALES del contenedor/OS cuentan: sin fichero pero con las
 * claves en el entorno, arranca.
 */
function appboot_loadenv(): void
{
    $pathEnv = BootPathsEnum::PATH_ROOT."/.env";
    appboot_loadenvfile($pathEnv);
    appboot_assertrequiredenvkeys($pathEnv);
}

function appboot_loadenvfile(string $pathEnv): void
{
    if (!is_file($pathEnv)) return;

    $replacements = [
        "%PATH_PUBLIC%" => BootPathsEnum::PATH_PUBLIC,
        "%PATH_ROOT%" => BootPathsEnum::PATH_ROOT,
        "%PATH_SRC%" => BootPathsEnum::PATH_SRC,
        "%PATH_SRC_CONFIG%" => BootPathsEnum::PATH_SRC_CONFIG,
    ];

    $lines = file($pathEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), "#") || !str_contains($line, "=")) {
            continue;
        }

        [$name, $value] = explode("=", $line, 2);
        $name = trim($name);
        if ($name === "") {
            continue;
        }

        // la variable de entorno REAL (contenedor/OS) gana sobre el fichero .env
        if (array_key_exists($name, $_SERVER) || array_key_exists($name, $_ENV)) {
            continue;
        }

        $value = strtr(appboot_getenvfilevalue($value), $replacements);
        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

}

/**
 * Valor tal cual lo escribiría dotenv: entre comillas ("x" o 'x') se toma el interior literal; sin comillas
 * se corta el comentario en línea (" # ..." o un valor que empieza por "#"). Antes el comentario y las comillas
 * se quedaban pegados al valor (TEST_PROMO_UUID=abc  # nota -> "abc  # nota").
 */
function appboot_getenvfilevalue(string $rawValue): string
{
    $value = trim($rawValue);
    if (preg_match('/^(["\'])(.*)\1/', $value, $matches)) {
        return $matches[2];
    }
    return trim((string) preg_replace('/(^|\s+)#.*$/', "", $value));
}

function appboot_assertrequiredenvkeys(string $pathEnv): void
{
    $missingKeys = appboot_getmissingenvkeys();
    if (!$missingKeys) return;

    $reason = is_file($pathEnv)
        ? "the .env file lacks them"
        : "missing file $pathEnv (copy .env.example to .env)";
    throw new RuntimeException(
        "appboot_loadenv: required env keys not set (".implode(", ", $missingKeys)."): $reason"
    );
}

/**
 * Claves obligatorias ausentes o vacías (en $_ENV, $_SERVER o el entorno real vía getenv()).
 */
function appboot_getmissingenvkeys(): array
{
    $requiredKeys = [
        AppEnvKeyEnum::APP_ENV,
        AppEnvKeyEnum::APP_DOMAIN,
        AppEnvKeyEnum::APP_CONTEXTS,
        AppEnvKeyEnum::APP_ENCDECRYPT,
    ];

    $missingKeys = [];
    foreach ($requiredKeys as $key) {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || trim((string) $value) === "") {
            $missingKeys[] = $key;
        }
    }
    return $missingKeys;
}

function dd($var, string $title = ""): void
{
    $sContent = var_export($var, true);
    if ($title) {
        echo "<b style=\"font-size: small; font-family: 'Roboto', 'sans-serif'\">$title</b>";
    }
    echo "<pre style=\"background:greenyellow;border:1px solid;\">"
        .$sContent
        ."</pre>";
    exit;
}

function lgerr($var, string $title = "", string $type = "error"): void
{
    if (!is_string($var)) {
        $var = var_export($var, true);
    }
    $content = $title ? "(lgerr) <<  $title >>".PHP_EOL.$var : "(lgerr)".PHP_EOL.$var;
    // Formato plano: storage/logs/YYYY-MM-DD-<type>.log (mismo esquema que LogTrait/Logger).
    Logger::getInstance()->writeLog("$type.log", $content, BootPathsEnum::PATH_LOGS);
}

function __(string $msgId): string
{
    if (!$msgId = trim($msgId)) {
        return "";
    }
    $args = func_get_args();
    array_shift($args);
    $msgChanged = $msgId;
    foreach ($args as $i => $str) {
        $rep = "{".$i."}";
        $msgChanged = str_replace($rep, (string) $str, $msgChanged);
    }

    $lang = strtolower(trim($_REQUEST["lang"] ?? "en"));
    if ($lang === "en") {
        return $msgChanged;
    }

    $pathPoFile = BootPathsEnum::PATH_LOCALE."/$lang/default.po";
    if (!is_file($pathPoFile)) {
        return $msgChanged;
    }

    if (!($_REQUEST["APP_TRANSLATIONS"][$lang] ?? [])) {
        $content = file_get_contents($pathPoFile);
        $content = trim($content);
        if (!$content) {
            return $msgChanged;
        }

        $lines = explode(PHP_EOL, $content);

        $trs = [];
        foreach ($lines as $i => $line) {

            if (!$line = trim($line)) {
                continue;
            }
            if (str_starts_with($line, "#")) {
                continue;
            }
            if (strstr($line, "msgstr \"")) {
                continue;
            }

            $id = str_replace("msgid \"", "", $line);
            $id = substr($id, 0, -1);
            $id = str_replace("\\\"", "\"", $id);

            $tr = trim($lines[$i + 1] ?? "");
            $tr = str_replace("msgstr \"", "", $tr);
            $tr = substr($tr, 0, -1);
            $tr = str_replace("\\\"", "\"", $tr);

            $trs[$id] = $tr;
        }
        $_REQUEST["APP_TRANSLATIONS"][$lang] = $trs;
        unset($lines, $trs);
    }

    $msgChanged = ($_REQUEST["APP_TRANSLATIONS"][$lang][$msgId] ?? "") ?: $msgId;

    foreach ($args as $i => $str) {
        $rep = "{".$i."}";
        $msgChanged = str_replace($rep, (string) $str, $msgChanged);
    }

    return $msgChanged;
}
