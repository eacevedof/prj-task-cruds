import { spawnSync } from "node:child_process";
import { existsSync, readFileSync } from "node:fs";
import { basename } from "node:path";

import { AbstractMailer, type MailAttachment } from "#app/Modules/Shared/Infrastructure/Components/Mailer/AbstractMailer.ts";
import { UuidGenerator } from "#app/Modules/Shared/Infrastructure/Components/Uuid/UuidGenerator.ts";
import { EnvironmentReaderRawRepository } from "#app/Modules/Shared/Infrastructure/Repositories/Configuration/EnvironmentReaderRawRepository.ts";

const DEFAULT_SENDMAIL_PATH = "/usr/sbin/sendmail -t -i";

/**
 * El mail() de PHP (de prj-mypromos, Components/Email/FuncEmailComponent): html y adjuntos opcionales, entregado al
 * comando de APP_SENDMAIL_PATH (el sendmail_path del php.ini). En local es el sendmail de busybox apuntando a
 * Mailpit (docker/app/Dockerfile).
 * Componente: no captura ni loguea, si no se puede enviar lanza Error.
 */
export class FuncMailer extends AbstractMailer {

    #boundary = "";

    static getInstance(): FuncMailer {
        return new FuncMailer();
    }

    /**
     * @throws Error
     */
    send(): void {
        if (!this.emailFrom || !this.emailsTo.length) {
            throw new Error("FuncMailer: email from and at least one email to are required");
        }

        this.#loadBoundary();
        this.#loadHeaderFrom();
        this.#loadHeaderMime();
        this.#loadHeaderCc();
        this.#loadHeaderBcc();

        let content = `${this.#getMultipart()}\n`;
        content += `${this.content}\n`;
        for (const attachment of this.attachments) {
            content += this.#getAttachment(attachment);
        }

        this.#sendmail(this.emailsTo.join(", "), this.subject, content, this.headers.join("\n"));
    }

    /**
     * Lo que hace mail(): To y Subject delante de las cabeceras, linea en blanco y el cuerpo, por stdin del sendmail.
     * @throws Error
     */
    #sendmail(to: string, subject: string, content: string, headers: string): void {
        const sendmailPath = EnvironmentReaderRawRepository.getInstance().getSendmailPath() || DEFAULT_SENDMAIL_PATH;
        const [sendmailCommand, ...sendmailArgs] = sendmailPath.split(/\s+/);

        const sendmailResult = spawnSync(sendmailCommand, sendmailArgs, {
            input: `To: ${to}\nSubject: ${subject}\n${headers}\n\n${content}`,
            encoding: "utf8",
        });
        if (sendmailResult.error) {
            throw new Error(`FuncMailer: mail() failed. ${sendmailResult.error.message}`);
        }
        if (sendmailResult.status !== 0) {
            throw new Error(`FuncMailer: mail() failed. exit ${sendmailResult.status} ${sendmailResult.stderr.trim()}`);
        }
    }

    #loadBoundary(): void {
        if (this.attachments.length) {
            this.#boundary = UuidGenerator.getInstance().getUuidV4().replaceAll("-", "");
        }
    }

    #loadHeaderFrom(): void {
        // nombre entre comillas: sin ellas, lo que va entre parentesis ("app (local)") es un comentario y se pierde
        const titleFrom = this.titleFrom.replaceAll("\"", "");
        this.headers.push(`From: "${titleFrom}" <${this.emailFrom}>`);
        this.headers.push(`Return-Path: <${this.emailFrom}>`);
        this.headers.push(`X-Sender: ${this.emailFrom}`);
    }

    #loadHeaderMime(): void {
        let headers = [
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=\"UTF-8\"",
            "Content-Transfer-Encoding: 8bit",
        ];

        if (this.#boundary) {
            headers = [
                "MIME-Version: 1.0",
                `Content-Type: multipart/mixed; boundary="${this.#boundary}"`,
                "Content-Transfer-Encoding: 7bit",
                "This is a MIME encoded message.",
            ];
        }
        this.headers.push(...headers);
    }

    #loadHeaderCc(): void {
        if (this.emailsCc.length) {
            this.headers.push(`cc: ${this.emailsCc.join(", ")}`);
        }
    }

    #loadHeaderBcc(): void {
        if (this.emailsBcc.length) {
            this.headers.push(`bcc: ${this.emailsBcc.join(", ")}`);
        }
    }

    #getMultipart(): string {
        if (!this.#boundary) {
            return "";
        }
        return [
            `--${this.#boundary}`,
            "Content-Type: text/html; charset=UTF-8",
            "Content-Transfer-Encoding: 8bit",
        ].join("\n");
    }

    /**
     * https://stackoverflow.com/questions/12301358/send-attachments-with-php-mail
     */
    #getAttachment(attachment: MailAttachment): string {
        const pathFile = attachment.path ?? "";
        if (!existsSync(pathFile)) {
            return "";
        }

        const content = readFileSync(pathFile);
        if (!content.length) {
            return "";
        }

        const mime = attachment.mime ?? "application/octet-stream";
        const alias = attachment.filename ?? basename(pathFile);

        return [
            "",
            `--${this.#boundary}`,
            `Content-Type: ${mime}; name="${alias}"`,
            "Content-Transfer-Encoding: base64",
            "Content-Disposition: attachment; ",
            this.#getChunkSplit(content.toString("base64")),
            `--${this.#boundary}--`,
            "",
        ].join("\n");
    }

    /**
     * chunk_split(): trozos de 76 caracteres con "\r\n" detras de cada uno.
     */
    #getChunkSplit(text: string): string {
        return (text.match(/.{1,76}/g) ?? []).map((chunk) => `${chunk}\r\n`).join("");
    }
}
