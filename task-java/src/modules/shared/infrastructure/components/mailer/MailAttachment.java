package modules.shared.infrastructure.components.mailer;

/**
 * Un adjunto: mime y filename vacios = application/octet-stream y el nombre del fichero.
 */
public record MailAttachment(String path, String mime, String filename) {
}
