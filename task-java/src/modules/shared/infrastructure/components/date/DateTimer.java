package modules.shared.infrastructure.components.date;

import java.time.LocalDateTime;
import java.time.ZoneId;
import java.time.ZonedDateTime;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * Fechas en texto "Y-m-d H:i:s" (el formato de la BD) entre zonas horarias. Los formatos son los de date() de PHP
 * con las letras que se usan (Y, m, d, H, i, s).
 * Sin las de zona por ip de task-php (ip-api.com): alli no las usa nadie.
 */
public final class DateTimer {

    public static final String TZ_UTC = "UTC";
    public static final String FORMAT_FULL_DT = "Y-m-d H:i:s";
    public static final String FORMAT_ONLY_DATE = "Y-m-d";

    private static final Pattern DT_PATTERN = Pattern.compile("^(\\d{4})-(\\d{2})-(\\d{2})(?: (\\d{2}):(\\d{2}):(\\d{2}))?$");
    private static final Pattern FORMAT_LETTER_PATTERN = Pattern.compile("[YmdHis]");

    public static DateTimer getInstance() {
        return new DateTimer();
    }

    public String getUtcDtInTargetTz(String utcDt) {
        return getUtcDtInTargetTz(utcDt, TZ_UTC, FORMAT_FULL_DT);
    }

    /**
     * de UTC a TZx: "2022-01-03 10:11:22" -> la misma hora en targetTz.
     * @throws java.time.DateTimeException si la zona no existe
     */
    public String getUtcDtInTargetTz(String utcDt, String targetTz, String format) {
        return getSourceDtIntoTargetTz(utcDt, TZ_UTC, targetTz, format);
    }

    public String getSourceDtIntoTargetTz(String sourceDt, String sourceTz) {
        return getSourceDtIntoTargetTz(sourceDt, sourceTz, TZ_UTC, FORMAT_FULL_DT);
    }

    /**
     * de TZx a TZy. Por defecto va de UI => BD (de UTCx a UTC0).
     * @throws IllegalArgumentException si la fecha no tiene el formato "Y-m-d H:i:s"
     * @throws java.time.DateTimeException si una zona no existe
     */
    public String getSourceDtIntoTargetTz(String sourceDt, String sourceTz, String targetTz, String format) {
        Matcher matcher = DT_PATTERN.matcher(sourceDt);
        if (!matcher.matches()) {
            throw new IllegalArgumentException("DateTimer: wrong date " + sourceDt);
        }
        LocalDateTime sourceLocalDateTime = LocalDateTime.of(
            Integer.parseInt(matcher.group(1)),
            Integer.parseInt(matcher.group(2)),
            Integer.parseInt(matcher.group(3)),
            matcher.group(4) == null ? 0 : Integer.parseInt(matcher.group(4)),
            matcher.group(5) == null ? 0 : Integer.parseInt(matcher.group(5)),
            matcher.group(6) == null ? 0 : Integer.parseInt(matcher.group(6))
        );
        ZonedDateTime targetDateTime = sourceLocalDateTime.atZone(ZoneId.of(sourceTz))
            .withZoneSameInstant(ZoneId.of(targetTz));
        return getFormatted(targetDateTime, format);
    }

    public String getNowDtIntoTargetTz() {
        return getNowDtIntoTargetTz(TZ_UTC, FORMAT_FULL_DT);
    }

    /**
     * fecha y hora actuales en TZx (por defecto UTC)
     */
    public String getNowDtIntoTargetTz(String targetTz, String format) {
        return getFormatted(ZonedDateTime.now(ZoneId.of(targetTz)), format);
    }

    /**
     * Cada letra de date() se cambia por su parte de la fecha: {Y: "2026", m: "01", ...}.
     */
    private String getFormatted(ZonedDateTime dateTime, String format) {
        Map<String, String> dateParts = Map.of(
            "Y", String.format("%04d", dateTime.getYear()),
            "m", String.format("%02d", dateTime.getMonthValue()),
            "d", String.format("%02d", dateTime.getDayOfMonth()),
            "H", String.format("%02d", dateTime.getHour()),
            "i", String.format("%02d", dateTime.getMinute()),
            "s", String.format("%02d", dateTime.getSecond())
        );
        return FORMAT_LETTER_PATTERN.matcher(format).replaceAll(matchResult -> dateParts.get(matchResult.group()));
    }
}
