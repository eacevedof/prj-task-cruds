from abc import abstractmethod

from Modules.Shared.Infrastructure.Components.Mailer.InterfaceMailer import InterfaceMailer


class AbstractMailer(InterfaceMailer):
    """
    Datos comunes de un email (de prj-mypromos, Components/Email/AbstractEmail). El envio lo hace cada mailer.
    Sin pila de errores: si no se puede enviar, send() lanza la excepcion.
    Un adjunto es un dict {"path": str, "mime": str (opcional), "filename": str (opcional)}.
    """

    def __init__(self):
        self.headers: list[str] = []
        self.emailFrom = ""
        self.titleFrom = ""
        self.emailsTo: list[str] = []
        self.emailsCc: list[str] = []
        self.emailsBcc: list[str] = []
        self.attachments: list[dict] = []

        self.subject = ""
        self.content = ""

    @abstractmethod
    def send(self) -> None:
        ...

    def setSubject(self, subject: str) -> "AbstractMailer":
        self.subject = subject
        return self

    def setFrom(self, email: str) -> "AbstractMailer":
        self.emailFrom = email
        return self

    def setTitleFrom(self, title: str) -> "AbstractMailer":
        self.titleFrom = title
        return self

    def addTo(self, email: str) -> "AbstractMailer":
        self.emailsTo.append(email)
        return self

    def addCc(self, email: str) -> "AbstractMailer":
        self.emailsCc.append(email)
        return self

    def addBcc(self, email: str) -> "AbstractMailer":
        self.emailsBcc.append(email)
        return self

    def setContent(self, content: str | list[str]) -> "AbstractMailer":
        """
        content html; una lista se une con saltos de linea
        """
        self.content = "\n".join(content) if isinstance(content, list) else content
        return self

    def addAttachment(self, attachment: dict) -> "AbstractMailer":
        self.attachments.append(attachment)
        return self
