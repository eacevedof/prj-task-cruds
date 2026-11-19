from Modules.Mailer.Domain.Enums.AlertEmailTypeEnum import AlertEmailTypeEnum
from Modules.Mailer.Application.SendAlertEmail.SendAlertEmailDto import SendAlertEmailDto
from Modules.Mailer.Application.SendAlertEmail.SendAlertEmailService import SendAlertEmailService

from Modules.Shared.Infrastructure.Exceptions.InterfaceException import (
    getThrowable,
    getThrowableFileAndLine,
    getThrowableTraceAsString,
)
from Modules.Shared.Infrastructure.Traits.LogTrait import LogTrait


class MailTrait:
    """
    Emails de alerta desde un controller o un command (de app-plataformabase / prj-mypromos, MailTrait).
    Se llama desde su catch: por eso aqui SI se captura, un fallo al enviar se loguea y nunca tapa el error
    original. Lleva su propio LogTrait (logException).
    """

    def __init__(self):
        self._logTrait = LogTrait.getInstance()

    @staticmethod
    def getInstance() -> "MailTrait":
        return MailTrait()

    def sendMailOnThrowable(self, throwable: BaseException, title: str) -> None:
        file, line = getThrowableFileAndLine(throwable)
        self._sendAlertEmail(AlertEmailTypeEnum.ERROR, title, "\n".join([
            f"{type(throwable).__name__}: {throwable}",
            f"File: {file} (line {line})",
            "",
            "Trace:",
            getThrowableTraceAsString(throwable),
        ]))

    def sendEmailOnSecurityThreat(self, message: str, subject: str = "") -> None:
        self._sendAlertEmail(AlertEmailTypeEnum.SECURITY_THREAT, subject, message)

    def sendEmailWarning(self, message: str, subject: str = "") -> None:
        self._sendAlertEmail(AlertEmailTypeEnum.WARNING, subject, message)

    def sendEmailNotice(self, message: str, subject: str) -> None:
        self._sendAlertEmail(AlertEmailTypeEnum.NOTICE, subject, message)

    def _sendAlertEmail(self, alertType: str, subject: str, details: str) -> None:
        try:
            SendAlertEmailService.getInstance().invoke(
                SendAlertEmailDto.fromPrimitives({
                    "alertType": alertType,
                    "subject": subject,
                    "details": details,
                })
            )
        except Exception as thrown:
            self._logTrait.logException(getThrowable(thrown), "MailTrait::sendAlertEmail")
