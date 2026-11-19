import os
import re
from datetime import datetime, timezone

from Modules.Shared.Infrastructure.Repositories.Configuration.EnvironmentReaderRawRepository import (
    EnvironmentReaderRawRepository,
)


class Logger:

    @staticmethod
    def getInstance() -> "Logger":
        return Logger()

    def writeLog(self, filePath: str, content: str, logsPath: str = "") -> None:
        logsPath = logsPath or EnvironmentReaderRawRepository.getInstance().getLogPath()
        if not logsPath:
            return

        if not os.path.exists(logsPath):
            os.makedirs(logsPath, mode=0o777, exist_ok=True)
            if not os.path.exists(logsPath):
                return

        logsFolderPath = os.path.realpath(logsPath)
        today, now = self._getUtcDateAndDateTime()
        logFilePath = f"{logsFolderPath}/{filePath}"

        finalDir = os.path.dirname(logFilePath)
        if not os.path.exists(finalDir):
            os.makedirs(finalDir, mode=0o777, exist_ok=True)

        name, extWithDot = os.path.splitext(os.path.basename(logFilePath))
        ext = extWithDot[1:] if extWithDot else "log"
        if "sql" in filePath:
            ext = "sql"
        fileName = f"{today}-{name}"

        logFilePath = f"{finalDir}/{fileName}.{ext}"
        content = content.strip()

        if ext == "sql":
            content = self._getNormalizedMarginForSql(content)

        isNewFile = not os.path.exists(logFilePath)
        with open(logFilePath, "a", encoding="utf-8") as logFile:
            logFile.write(f"\n[{now}]\n{content}")
        if isNewFile:
            try:
                os.chmod(logFilePath, 0o666)
            except OSError:
                pass

    def _getNormalizedMarginForSql(self, content: str) -> str:
        content = f"{content}\n"
        return re.sub(r"^ {8}", "", content, flags=re.MULTILINE)

    def _getUtcDateAndDateTime(self) -> tuple[str, str]:
        """
        date("Y-m-d") y date("Y-m-d H:i:s") con la zona de la app (UTC).
        """
        nowUtc = datetime.now(timezone.utc)
        return nowUtc.strftime("%Y-%m-%d"), nowUtc.strftime("%Y-%m-%d %H:%M:%S")
