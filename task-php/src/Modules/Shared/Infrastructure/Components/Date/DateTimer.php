<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Components\Date;

use DateTime;
use DateTimeZone;

final class DateTimer
{
    public const string TZ_UTC = "UTC";
    public const string FORMAT_FULL_DT = "Y-m-d H:i:s";
    public const string FORMAT_ONLY_DATE = "Y-m-d";

    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * de UTC a TZx
     * @param string $utcdt "2022-01-03 10:11:22"
     * @param string $targettz user-timezone
     * @param string $format "Y-m-d H:i:s",
     * @return string
     * @throws \Exception
     */
    public function getUtcDtInTargetTz(
        string $utcdt,
        string $targettz = self::TZ_UTC,
        string $format = self::FORMAT_FULL_DT
    ): string {
        $source = new DateTime($utcdt, new DateTimeZone(self::TZ_UTC));
        $source->setTimezone(new DateTimeZone($targettz));
        return $source->format($format);
    }

    /**
     * de TZx a UTC
     * Por defecto va de UI => BD. De UTCx a UTC0
     * @param string $sourcedt "2022-01-03 10:11:22"
     * @param string $sourcetz "Europe/Madrid"
     * @param string $targettz "UTC"
     * @param string $format "Y-m-d H:i:s",
     * @return string
     * @throws \Exception
     */
    public function getSourceDtIntoTargetTz(
        string $sourcedt,
        string $sourcetz,
        string $targettz = self::TZ_UTC,
        string $format = self::FORMAT_FULL_DT
    ): string {
        $source = new DateTime($sourcedt, new DateTimeZone($sourcetz));
        $source->setTimezone(new DateTimeZone($targettz));
        return $source->format($format);
    }

    /**
     * current datetime in TZx (default UTC)
     * @param string $targetTz
     * @param string $format
     * @return string
     * @throws \Exception
     */
    public function getNowDtIntoTargetTz(string $targetTz = self::TZ_UTC, string $format = self::FORMAT_FULL_DT): string
    {
        $dt = new DateTime("now", new DateTimeZone($targetTz));
        return $dt->format($format);
    }

    /**
     * Zona horaria de una IP via ip-api.com. Es un servicio externo (limite 45 req/min, sin SLA): si
     * no responde, limita o devuelve basura, la pagina NO puede romperse -> UTC. En prod llego a dar
     * 500 en /afiliado/:slug (json_decode(false)) consultando la IP interna del proxy (10.0.1.15).
     */
    public function getTimezoneByIp(string $ip): string
    {
        // sin IP publica (local, red docker, proxy) no hay nada que geolocalizar
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return self::TZ_UTC;
        }
        $context = stream_context_create(["http" => ["timeout" => 2, "ignore_errors" => true]]);
        // @: el fallo de red ya se trata (false -> UTC); sin el, cada caida del servicio llena el log de warnings
        $json = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,timezone", false, $context);
        if ($json === false) {
            return self::TZ_UTC;
        }
        $info = json_decode($json, true);
        /*
         * para que esto funcione hay que instalar una extension
        $info = geoip_record_by_name($ip);
        $timezone = geoip_time_zone_by_country_and_region($info["country_code"], $info["region"]);
        $info["timezone"] = $timezone;
        */

        $timezone = is_array($info) ? (string) ($info["timezone"] ?? "") : "";
        return in_array($timezone, DateTimeZone::listIdentifiers(), true) ? $timezone : self::TZ_UTC;
    }

    public function getUtcDateIntoIpTimeZone(
        string $utc,
        string $targetIp,
        string $format = self::FORMAT_FULL_DT
    ): string {
        $timezoneByIp = $this->getTimezoneByIp($targetIp);
        if ($timezoneByIp === self::TZ_UTC) {
            return date($format, strtotime($utc));
        }
        return $this->getSourceDtIntoTargetTz($utc, self::TZ_UTC, $timezoneByIp, $format);
    }

    private function getSecsDiffBetweenTzs(string $targettz, string $sourcetz = self::TZ_UTC): int
    {
        $tz0 = new DateTimeZone($sourcetz);
        //$tzTarget = new DateTimeZone("Europe/Madrid");
        $tzTarget = new DateTimeZone($targettz);

        $dt0 = new DateTime("now", $tz0);
        //$dtTarget = new DateTime("now", $tzTarget);
        $secsoffset = $tzTarget->getOffset($dt0);
        return (int) $secsoffset;
    }

    private function getDateByIp(string $ip, string $format = self::FORMAT_FULL_DT): string
    {
        $timezone = $this->getTimezoneByIp($ip);
        return $this->getNowDtIntoTargetTz($timezone, $format);
    }
}
