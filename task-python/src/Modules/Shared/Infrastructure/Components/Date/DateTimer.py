import re
from datetime import datetime, timezone
from zoneinfo import ZoneInfo, ZoneInfoNotFoundError


class DateTimer:
    """
    Fechas en texto "Y-m-d H:i:s" (el formato de la BD) entre zonas horarias. Los formatos son los de date() de PHP
    con las letras que se usan (Y, m, d, H, i, s).
    Sin las de zona por ip de task-php (ip-api.com): alli no las usa nadie.
    """

    TZ_UTC = "UTC"
    FORMAT_FULL_DT = "Y-m-d H:i:s"
    FORMAT_ONLY_DATE = "Y-m-d"

    @staticmethod
    def getInstance() -> "DateTimer":
        return DateTimer()

    def getUtcDtInTargetTz(self, utcDt: str, targetTz: str = "UTC", format: str = "Y-m-d H:i:s") -> str:
        """
        de UTC a TZx: "2022-01-03 10:11:22" -> la misma hora en targetTz.
        @throws ValueError si la zona no existe
        """
        return self.getSourceDtIntoTargetTz(utcDt, DateTimer.TZ_UTC, targetTz, format)

    def getSourceDtIntoTargetTz(
        self, sourceDt: str, sourceTz: str, targetTz: str = "UTC", format: str = "Y-m-d H:i:s"
    ) -> str:
        """
        de TZx a TZy. Por defecto va de UI => BD (de UTCx a UTC0).
        @throws ValueError si una zona no existe o la fecha no tiene el formato "Y-m-d H:i:s"
        """
        matches = re.match(r"^(\d{4})-(\d{2})-(\d{2})(?: (\d{2}):(\d{2}):(\d{2}))?$", sourceDt)
        if not matches:
            raise ValueError(f"DateTimer: wrong date {sourceDt}")
        year, month, day, hours, minutes, seconds = (int(part or 0) for part in matches.groups())
        try:
            # la hora de pared es la de sourceTz; astimezone la pasa al instante equivalente en targetTz
            sourceMoment = datetime(year, month, day, hours, minutes, seconds, tzinfo=self._getTimeZone(sourceTz))
        except OverflowError as error:
            raise ValueError(f"DateTimer: wrong date {sourceDt}") from error
        return self._getFormatted(sourceMoment.astimezone(self._getTimeZone(targetTz)), format)

    def getNowDtIntoTargetTz(self, targetTz: str = "UTC", format: str = "Y-m-d H:i:s") -> str:
        """
        fecha y hora actuales en TZx (por defecto UTC)
        """
        return self._getFormatted(datetime.now(timezone.utc).astimezone(self._getTimeZone(targetTz)), format)

    def _getTimeZone(self, timeZone: str):
        if timeZone == DateTimer.TZ_UTC:
            return timezone.utc
        try:
            return ZoneInfo(timeZone)
        except (ZoneInfoNotFoundError, ValueError, OSError) as error:
            raise ValueError(f"DateTimer: wrong time zone {timeZone}") from error

    def _getFormatted(self, moment: datetime, format: str) -> str:
        dateParts = {
            "Y": f"{moment.year:04d}",
            "m": f"{moment.month:02d}",
            "d": f"{moment.day:02d}",
            "H": f"{moment.hour:02d}",
            "i": f"{moment.minute:02d}",
            "s": f"{moment.second:02d}",
        }
        return re.sub(r"[YmdHis]", lambda letter: dateParts[letter.group(0)], format)
