import base64
import os
import re
import subprocess

from Modules.Shared.Infrastructure.Components.Mailer.AbstractMailer import AbstractMailer
from Modules.Shared.Infrastructure.Components.Uuid.UuidGenerator import UuidGenerator
from Modules.Shared.Infrastructure.Repositories.Configuration.EnvironmentReaderRawRepository import (
    EnvironmentReaderRawRepository,
)

_DEFAULT_SENDMAIL_PATH = "/usr/sbin/sendmail -t -i"


class FuncMailer(AbstractMailer):
    """
    El mail() de PHP (de prj-mypromos, Components/Email/FuncEmailComponent): html y adjuntos opcionales, entregado al
    comando de APP_SENDMAIL_PATH (el sendmail_path del php.ini). En local es el sendmail de busybox apuntando a
    Mailpit (docker/app/Dockerfile).
    Componente: no captura ni loguea, si no se puede enviar lanza RuntimeError.
    """

    def __init__(self):
        super().__init__()
        self._boundary = ""

    @staticmethod
    def getInstance() -> "FuncMailer":
        return FuncMailer()

    def send(self) -> None:
        """
        @throws RuntimeError
        """
        if not self.emailFrom or not self.emailsTo:
            raise RuntimeError("FuncMailer: email from and at least one email to are required")

        self._loadBoundary()
        self._loadHeaderFrom()
        self._loadHeaderMime()
        self._loadHeaderCc()
        self._loadHeaderBcc()

        content = f"{self._getMultipart()}\n"
        content += f"{self.content}\n"
        for attachment in self.attachments:
            content += self._getAttachment(attachment)

        self._sendmail(", ".join(self.emailsTo), self.subject, content, "\n".join(self.headers))

    def _sendmail(self, to: str, subject: str, content: str, headers: str) -> None:
        """
        Lo que hace mail(): To y Subject delante de las cabeceras, linea en blanco y el cuerpo, por stdin del sendmail.
        @throws RuntimeError
        """
        sendmailPath = EnvironmentReaderRawRepository.getInstance().getSendmailPath() or _DEFAULT_SENDMAIL_PATH
        sendmailCommand = re.split(r"\s+", sendmailPath.strip())

        try:
            sendmailResult = subprocess.run(
                sendmailCommand,
                input=f"To: {to}\nSubject: {subject}\n{headers}\n\n{content}",
                capture_output=True,
                text=True,
                encoding="utf-8",
                check=False,
            )
        except OSError as error:
            raise RuntimeError(f"FuncMailer: mail() failed. {error}") from error
        if sendmailResult.returncode != 0:
            raise RuntimeError(
                f"FuncMailer: mail() failed. exit {sendmailResult.returncode} {sendmailResult.stderr.strip()}"
            )

    def _loadBoundary(self) -> None:
        if self.attachments:
            self._boundary = UuidGenerator.getInstance().getUuidV4().replace("-", "")

    def _loadHeaderFrom(self) -> None:
        # nombre entre comillas: sin ellas, lo que va entre parentesis ("app (local)") es un comentario y se pierde
        titleFrom = self.titleFrom.replace("\"", "")
        self.headers.append(f"From: \"{titleFrom}\" <{self.emailFrom}>")
        self.headers.append(f"Return-Path: <{self.emailFrom}>")
        self.headers.append(f"X-Sender: {self.emailFrom}")

    def _loadHeaderMime(self) -> None:
        headers = [
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=\"UTF-8\"",
            "Content-Transfer-Encoding: 8bit",
        ]

        if self._boundary:
            headers = [
                "MIME-Version: 1.0",
                f"Content-Type: multipart/mixed; boundary=\"{self._boundary}\"",
                "Content-Transfer-Encoding: 7bit",
                "This is a MIME encoded message.",
            ]
        self.headers.extend(headers)

    def _loadHeaderCc(self) -> None:
        if self.emailsCc:
            self.headers.append(f"cc: {', '.join(self.emailsCc)}")

    def _loadHeaderBcc(self) -> None:
        if self.emailsBcc:
            self.headers.append(f"bcc: {', '.join(self.emailsBcc)}")

    def _getMultipart(self) -> str:
        if not self._boundary:
            return ""
        return "\n".join([
            f"--{self._boundary}",
            "Content-Type: text/html; charset=UTF-8",
            "Content-Transfer-Encoding: 8bit",
        ])

    def _getAttachment(self, attachment: dict) -> str:
        """
        https://stackoverflow.com/questions/12301358/send-attachments-with-php-mail
        """
        pathFile = attachment.get("path") or ""
        if not os.path.exists(pathFile):
            return ""

        with open(pathFile, "rb") as attachmentFile:
            content = attachmentFile.read()
        if not content:
            return ""

        mime = attachment.get("mime") or "application/octet-stream"
        alias = attachment.get("filename") or os.path.basename(pathFile)

        return "\n".join([
            "",
            f"--{self._boundary}",
            f"Content-Type: {mime}; name=\"{alias}\"",
            "Content-Transfer-Encoding: base64",
            "Content-Disposition: attachment; ",
            self._getChunkSplit(base64.b64encode(content).decode("ascii")),
            f"--{self._boundary}--",
            "",
        ])

    def _getChunkSplit(self, text: str) -> str:
        """
        chunk_split(): trozos de 76 caracteres con "\\r\\n" detras de cada uno.
        """
        return "".join(f"{text[i:i + 76]}\r\n" for i in range(0, len(text), 76))
