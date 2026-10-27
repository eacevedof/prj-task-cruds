package modules.shared.infrastructure.components.mailer;

import java.util.ArrayList;
import java.util.List;

/**
 * Datos comunes de un email (de prj-mypromos, Components/Email/AbstractEmail). El envio lo hace cada mailer.
 * Sin pila de errores: si no se puede enviar, send() lanza la excepcion.
 */
public abstract class AbstractMailer implements InterfaceMailer {

    protected final List<String> headers = new ArrayList<>();
    protected String emailFrom = "";
    protected String titleFrom = "";
    protected final List<String> emailsTo = new ArrayList<>();
    protected final List<String> emailsCc = new ArrayList<>();
    protected final List<String> emailsBcc = new ArrayList<>();
    protected final List<MailAttachment> attachments = new ArrayList<>();

    protected String subject = "";
    protected String content = "";

    @Override
    public abstract void send();

    public AbstractMailer setSubject(String subject) {
        this.subject = subject;
        return this;
    }

    public AbstractMailer setFrom(String email) {
        this.emailFrom = email;
        return this;
    }

    public AbstractMailer setTitleFrom(String title) {
        this.titleFrom = title;
        return this;
    }

    public AbstractMailer addTo(String email) {
        emailsTo.add(email);
        return this;
    }

    public AbstractMailer addCc(String email) {
        emailsCc.add(email);
        return this;
    }

    public AbstractMailer addBcc(String email) {
        emailsBcc.add(email);
        return this;
    }

    /**
     * content html
     */
    public AbstractMailer setContent(String content) {
        this.content = content;
        return this;
    }

    /**
     * content html; las lineas se unen con saltos de linea
     */
    public AbstractMailer setContent(List<String> contentLines) {
        this.content = String.join("\n", contentLines);
        return this;
    }

    public AbstractMailer addAttachment(MailAttachment attachment) {
        attachments.add(attachment);
        return this;
    }
}
