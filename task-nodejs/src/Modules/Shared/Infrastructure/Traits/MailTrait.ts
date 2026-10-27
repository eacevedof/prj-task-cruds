import { AlertEmailTypeEnum } from "#app/Modules/Mailer/Domain/Enums/AlertEmailTypeEnum.ts";
import { SendAlertEmailDto } from "#app/Modules/Mailer/Application/SendAlertEmail/SendAlertEmailDto.ts";
import { SendAlertEmailService } from "#app/Modules/Mailer/Application/SendAlertEmail/SendAlertEmailService.ts";

import {
    getThrowable,
    getThrowableFileAndLine,
    getThrowableTraceAsString,
} from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";
import { LogTrait } from "#app/Modules/Shared/Infrastructure/Traits/LogTrait.ts";

/**
 * Emails de alerta desde un controller o un command (de app-plataformabase / prj-mypromos, MailTrait).
 * Se llama desde su catch: por eso aqui SI se captura, un fallo al enviar se loguea y nunca tapa el error
 * original. Lleva su propio LogTrait (logException).
 */
export class MailTrait {

    readonly #logTrait: LogTrait;

    constructor() {
        this.#logTrait = LogTrait.getInstance();
    }

    static getInstance(): MailTrait {
        return new MailTrait();
    }

    sendMailOnThrowable(throwable: Error, title: string): void {
        const [file, line] = getThrowableFileAndLine(throwable);
        this.#sendAlertEmail(AlertEmailTypeEnum.ERROR, title, [
            `${throwable.name}: ${throwable.message}`,
            `File: ${file} (line ${line})`,
            "",
            "Trace:",
            getThrowableTraceAsString(throwable),
        ].join("\n"));
    }

    sendEmailOnSecurityThreat(message: string, subject: string = ""): void {
        this.#sendAlertEmail(AlertEmailTypeEnum.SECURITY_THREAT, subject, message);
    }

    sendEmailWarning(message: string, subject: string = ""): void {
        this.#sendAlertEmail(AlertEmailTypeEnum.WARNING, subject, message);
    }

    sendEmailNotice(message: string, subject: string): void {
        this.#sendAlertEmail(AlertEmailTypeEnum.NOTICE, subject, message);
    }

    #sendAlertEmail(alertType: string, subject: string, details: string): void {
        try {
            SendAlertEmailService.getInstance().invoke(
                SendAlertEmailDto.fromPrimitives({
                    alertType: alertType,
                    subject: subject,
                    details: details,
                })
            );
        }
        catch (thrown) {
            this.#logTrait.logException(getThrowable(thrown), "MailTrait::sendAlertEmail");
        }
    }
}
