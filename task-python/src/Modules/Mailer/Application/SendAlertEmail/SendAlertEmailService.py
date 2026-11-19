from Modules.Shared.Infrastructure.Components.Date.DateTimer import DateTimer
from Modules.Shared.Infrastructure.Components.Html.HtmlEscaper import HtmlEscaper
from Modules.Shared.Infrastructure.Components.Mailer.FuncMailer import FuncMailer
from Modules.Shared.Infrastructure.Components.Server.ServerIdentifier import ServerIdentifier
from Modules.Shared.Infrastructure.Repositories.Configuration.EnvironmentReaderRawRepository import EnvironmentReaderRawRepository

from Modules.Mailer.Application.SendAlertEmail.SendAlertEmailDto import SendAlertEmailDto
from Modules.Mailer.Application.SendAlertEmail.SendAlertEmailResultDto import SendAlertEmailResultDto
from Modules.Mailer.Domain.Enums.AlertEmailTypeEnum import AlertEmailTypeEnum


class SendAlertEmailService:
    """
    Email de alerta al equipo (error, amenaza de seguridad, warning, notice) a APP_EMAIL_TO.
    Sin APP_EMAIL_TO no envia (alertas desactivadas). No captura: si el envio falla escala, y quien lo usa
    (MailTrait) decide que hacer para no tapar el error original.
    """

    def __init__(self):
        self._dateTimer = DateTimer.getInstance()
        self._funcMailer = FuncMailer.getInstance()
        self._htmlEscaper = HtmlEscaper.getInstance()
        self._serverIdentifier = ServerIdentifier.getInstance()
        self._environmentReaderRawRepository = EnvironmentReaderRawRepository.getInstance()

        self._sendAlertEmailDto: SendAlertEmailDto

    @staticmethod
    def getInstance() -> "SendAlertEmailService":
        return SendAlertEmailService()

    def invoke(self, sendAlertEmailDto: SendAlertEmailDto) -> SendAlertEmailResultDto:
        """
        @throws Exception
        """
        self._sendAlertEmailDto = sendAlertEmailDto
        self._failIfWrongInput()

        emailTo = self._environmentReaderRawRepository.getEmailTo()
        if not emailTo:
            return SendAlertEmailResultDto.fromPrimitives({"isSent": False, "emailTo": ""})

        environment = self._environmentReaderRawRepository.getEnvironment()
        appName = self._environmentReaderRawRepository.getAppName()
        (self._funcMailer
            .setFrom(self._environmentReaderRawRepository.getEmailFrom() or emailTo)
            .setTitleFrom(f"{appName} ({environment})")
            .addTo(emailTo)
            .setSubject(self._getSubject(environment))
            .setContent(self._getHtmlBody())
            .send())

        return SendAlertEmailResultDto.fromPrimitives({"isSent": True, "emailTo": emailTo})

    def _failIfWrongInput(self) -> None:
        """
        La alerta la genera el codigo (MailTrait), no un usuario: un dato mal formado es un fallo de programacion.
        @throws Exception
        """
        alertType = self._sendAlertEmailDto.getAlertType()
        if alertType not in AlertEmailTypeEnum.SUBJECT_TAGS:
            raise Exception(f'SendAlertEmail: unknown alert type "{alertType}"')
        if not self._sendAlertEmailDto.getSubject():
            raise Exception("SendAlertEmail: subject is required")

    def _getSubject(self, environment: str) -> str:
        subjectTag = AlertEmailTypeEnum.SUBJECT_TAGS[self._sendAlertEmailDto.getAlertType()]
        serverInfo = f"{self._serverIdentifier.getServerName()} ({self._serverIdentifier.getServerIp()})"
        return f"{subjectTag} ({environment}) {serverInfo} - {self._sendAlertEmailDto.getSubject()}"

    def _getHtmlBody(self) -> str:
        alertType = self._sendAlertEmailDto.getAlertType()
        heading = AlertEmailTypeEnum.HEADINGS[alertType]
        color = AlertEmailTypeEnum.COLORS[alertType]
        now = self._dateTimer.getNowDtIntoTargetTz()
        baseUrl = self._htmlEscaper.getEscapedHtml(self._environmentReaderRawRepository.getBaseUrl())
        subject = self._htmlEscaper.getEscapedHtml(self._sendAlertEmailDto.getSubject())
        details = self._htmlEscaper.getEscapedHtml(self._sendAlertEmailDto.getDetails())

        return f"""
            <h3 style="color:{color};">[{now}] {heading}: {subject}</h3>
            <p><a href="{baseUrl}" target="_blank">{baseUrl}</a></p>
            <pre>{details}</pre>
        """
