import type { InterfaceMailer } from "#app/Modules/Shared/Infrastructure/Components/Mailer/InterfaceMailer.ts";

export type MailAttachment = {
    path: string;
    mime?: string;
    filename?: string;
};

/**
 * Datos comunes de un email (de prj-mypromos, Components/Email/AbstractEmail). El envio lo hace cada mailer.
 * Sin pila de errores: si no se puede enviar, send() lanza la excepcion.
 */
export abstract class AbstractMailer implements InterfaceMailer {

    protected headers: string[] = [];
    protected emailFrom = "";
    protected titleFrom = "";
    protected emailsTo: string[] = [];
    protected emailsCc: string[] = [];
    protected emailsBcc: string[] = [];
    protected attachments: MailAttachment[] = [];

    protected subject = "";
    protected content = "";

    abstract send(): void;

    setSubject(subject: string): this {
        this.subject = subject;
        return this;
    }

    setFrom(email: string): this {
        this.emailFrom = email;
        return this;
    }

    setTitleFrom(title: string): this {
        this.titleFrom = title;
        return this;
    }

    addTo(email: string): this {
        this.emailsTo.push(email);
        return this;
    }

    addCc(email: string): this {
        this.emailsCc.push(email);
        return this;
    }

    addBcc(email: string): this {
        this.emailsBcc.push(email);
        return this;
    }

    /**
     * content html; un array se une con saltos de linea
     */
    setContent(content: string | string[]): this {
        this.content = Array.isArray(content) ? content.join("\n") : content;
        return this;
    }

    addAttachment(attachment: MailAttachment): this {
        this.attachments.push(attachment);
        return this;
    }
}
