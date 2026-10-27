package modules.shared.infrastructure.components.mailer;

import java.io.IOException;
import java.io.OutputStream;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.Base64;
import java.util.List;

import modules.shared.infrastructure.components.uuid.UuidGenerator;
import modules.shared.infrastructure.repositories.configuration.EnvironmentReaderRawRepository;

/**
 * El mail() de PHP (de prj-mypromos, Components/Email/FuncEmailComponent): html y adjuntos opcionales, entregado al
 * comando de APP_SENDMAIL_PATH (el sendmail_path del php.ini). En local es el sendmail de busybox apuntando a
 * Mailpit (docker/app/Dockerfile).
 * Componente: no captura ni loguea, si no se puede enviar lanza IllegalStateException.
 */
public final class FuncMailer extends AbstractMailer {

    private static final String DEFAULT_SENDMAIL_PATH = "/usr/sbin/sendmail -t -i";
    private static final int BASE64_LINE_LENGTH = 76;

    private String boundary = "";

    public static FuncMailer getInstance() {
        return new FuncMailer();
    }

    /**
     * @throws IllegalStateException
     */
    @Override
    public void send() {
        if (emailFrom.isEmpty() || emailsTo.isEmpty()) {
            throw new IllegalStateException("FuncMailer: email from and at least one email to are required");
        }

        loadBoundary();
        loadHeaderFrom();
        loadHeaderMime();
        loadHeaderCc();
        loadHeaderBcc();

        StringBuilder mailContent = new StringBuilder(getMultipart()).append("\n");
        mailContent.append(content).append("\n");
        for (MailAttachment attachment : attachments) {
            mailContent.append(getAttachment(attachment));
        }

        sendmail(String.join(", ", emailsTo), subject, mailContent.toString(), String.join("\n", headers));
    }

    /**
     * Lo que hace mail(): To y Subject delante de las cabeceras, linea en blanco y el cuerpo, por stdin del sendmail.
     * @throws IllegalStateException
     */
    private void sendmail(String to, String subject, String content, String headers) {
        String sendmailPath = EnvironmentReaderRawRepository.getInstance().getSendmailPath();
        List<String> sendmailCommand = Arrays.asList((sendmailPath.isEmpty() ? DEFAULT_SENDMAIL_PATH : sendmailPath).split("\\s+"));

        String input = "To: " + to + "\nSubject: " + subject + "\n" + headers + "\n\n" + content;
        try {
            Process sendmailProcess = new ProcessBuilder(sendmailCommand).redirectOutput(ProcessBuilder.Redirect.DISCARD).start();
            try (OutputStream stdin = sendmailProcess.getOutputStream()) {
                stdin.write(input.getBytes(StandardCharsets.UTF_8));
            }
            String stderr = new String(sendmailProcess.getErrorStream().readAllBytes(), StandardCharsets.UTF_8);
            int exitCode = sendmailProcess.waitFor();
            if (exitCode != 0) {
                throw new IllegalStateException("FuncMailer: mail() failed. exit " + exitCode + " " + stderr.trim());
            }
        }
        catch (IOException e) {
            throw new IllegalStateException("FuncMailer: mail() failed. " + e.getMessage(), e);
        }
        catch (InterruptedException e) {
            Thread.currentThread().interrupt();
            throw new IllegalStateException("FuncMailer: mail() interrupted", e);
        }
    }

    private void loadBoundary() {
        if (!attachments.isEmpty()) {
            boundary = UuidGenerator.getInstance().getUuidV4().replace("-", "");
        }
    }

    private void loadHeaderFrom() {
        // nombre entre comillas: sin ellas, lo que va entre parentesis ("app (local)") es un comentario y se pierde
        String quotedTitleFrom = titleFrom.replace("\"", "");
        headers.add("From: \"" + quotedTitleFrom + "\" <" + emailFrom + ">");
        headers.add("Return-Path: <" + emailFrom + ">");
        headers.add("X-Sender: " + emailFrom);
    }

    private void loadHeaderMime() {
        if (!boundary.isEmpty()) {
            headers.addAll(List.of(
                "MIME-Version: 1.0",
                "Content-Type: multipart/mixed; boundary=\"" + boundary + "\"",
                "Content-Transfer-Encoding: 7bit",
                "This is a MIME encoded message."
            ));
            return;
        }
        headers.addAll(List.of(
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=\"UTF-8\"",
            "Content-Transfer-Encoding: 8bit"
        ));
    }

    private void loadHeaderCc() {
        if (!emailsCc.isEmpty()) {
            headers.add("cc: " + String.join(", ", emailsCc));
        }
    }

    private void loadHeaderBcc() {
        if (!emailsBcc.isEmpty()) {
            headers.add("bcc: " + String.join(", ", emailsBcc));
        }
    }

    private String getMultipart() {
        if (boundary.isEmpty()) {
            return "";
        }
        return String.join("\n",
            "--" + boundary,
            "Content-Type: text/html; charset=UTF-8",
            "Content-Transfer-Encoding: 8bit"
        );
    }

    /**
     * https://stackoverflow.com/questions/12301358/send-attachments-with-php-mail
     */
    private String getAttachment(MailAttachment attachment) {
        Path pathFile = Path.of(attachment.path() == null ? "" : attachment.path());
        if (!Files.isRegularFile(pathFile)) {
            return "";
        }

        byte[] fileContent;
        try {
            fileContent = Files.readAllBytes(pathFile);
        }
        catch (IOException e) {
            return "";
        }
        if (fileContent.length == 0) {
            return "";
        }

        String mime = attachment.mime() == null || attachment.mime().isEmpty() ? "application/octet-stream" : attachment.mime();
        String alias = attachment.filename() == null || attachment.filename().isEmpty()
            ? pathFile.getFileName().toString()
            : attachment.filename();

        return String.join("\n",
            "",
            "--" + boundary,
            "Content-Type: " + mime + "; name=\"" + alias + "\"",
            "Content-Transfer-Encoding: base64",
            "Content-Disposition: attachment; ",
            getChunkSplit(Base64.getEncoder().encodeToString(fileContent)),
            "--" + boundary + "--",
            ""
        );
    }

    /**
     * chunk_split(): trozos de 76 caracteres con "\r\n" detras de cada uno.
     */
    private String getChunkSplit(String text) {
        List<String> chunks = new ArrayList<>();
        for (int i = 0; i < text.length(); i += BASE64_LINE_LENGTH) {
            chunks.add(text.substring(i, Math.min(text.length(), i + BASE64_LINE_LENGTH)) + "\r\n");
        }
        return String.join("", chunks);
    }
}
