import { DateTimer } from "#app/Modules/Shared/Infrastructure/Components/Date/DateTimer.ts";
import { HtmlEscaper } from "#app/Modules/Shared/Infrastructure/Components/Html/HtmlEscaper.ts";
import { FuncMailer } from "#app/Modules/Shared/Infrastructure/Components/Mailer/FuncMailer.ts";
import { ServerIdentifier } from "#app/Modules/Shared/Infrastructure/Components/Server/ServerIdentifier.ts";
import { EnvironmentReaderRawRepository } from "#app/Modules/Shared/Infrastructure/Repositories/Configuration/EnvironmentReaderRawRepository.ts";

import type { SendAlertEmailDto } from "#app/Modules/Mailer/Application/SendAlertEmail/SendAlertEmailDto.ts";
import { SendAlertEmailResultDto } from "#app/Modules/Mailer/Application/SendAlertEmail/SendAlertEmailResultDto.ts";
import { AlertEmailTypeEnum } from "#app/Modules/Mailer/Domain/Enums/AlertEmailTypeEnum.ts";

/**
 * Email de alerta al equipo (error, amenaza de seguridad, warning, notice) a APP_EMAIL_TO.
 * Sin APP_EMAIL_TO no envia (alertas desactivadas). No captura: si el envio falla escala, y quien lo usa
 * (MailTrait) decide que hacer para no tapar el error original.
 */
export class SendAlertEmailService {

    readonly #dateTimer: DateTimer;
    readonly #funcMailer: FuncMailer;
    readonly #htmlEscaper: HtmlEscaper;
    readonly #serverIdentifier: ServerIdentifier;
    readonly #environmentReaderRawRepository: EnvironmentReaderRawRepository;

    #sendAlertEmailDto!: SendAlertEmailDto;

    constructor() {
        this.#dateTimer = DateTimer.getInstance();
        this.#funcMailer = FuncMailer.getInstance();
        this.#htmlEscaper = HtmlEscaper.getInstance();
        this.#serverIdentifier = ServerIdentifier.getInstance();
        this.#environmentReaderRawRepository = EnvironmentReaderRawRepository.getInstance();
    }

    static getInstance(): SendAlertEmailService {
        return new SendAlertEmailService();
    }

    /**
     * @throws Error
     */
    invoke(sendAlertEmailDto: SendAlertEmailDto): SendAlertEmailResultDto {
        this.#sendAlertEmailDto = sendAlertEmailDto;
        this.#failIfWrongInput();

        const emailTo = this.#environmentReaderRawRepository.getEmailTo();
        if (!emailTo) {
            return SendAlertEmailResultDto.fromPrimitives({ isSent: false, emailTo: "" });
        }

        const environment = this.#environmentReaderRawRepository.getEnvironment();
        const appName = this.#environmentReaderRawRepository.getAppName();
        this.#funcMailer
            .setFrom(this.#environmentReaderRawRepository.getEmailFrom() || emailTo)
            .setTitleFrom(`${appName} (${environment})`)
            .addTo(emailTo)
            .setSubject(this.#getSubject(environment))
            .setContent(this.#getHtmlBody())
            .send();

        return SendAlertEmailResultDto.fromPrimitives({ isSent: true, emailTo: emailTo });
    }

    /**
     * La alerta la genera el codigo (MailTrait), no un usuario: un dato mal formado es un fallo de programacion.
     * @throws Error
     */
    #failIfWrongInput(): void {
        const alertType = this.#sendAlertEmailDto.getAlertType();
        if (!(alertType in AlertEmailTypeEnum.SUBJECT_TAGS)) {
            throw new Error(`SendAlertEmail: unknown alert type "${alertType}"`);
        }
        if (!this.#sendAlertEmailDto.getSubject()) {
            throw new Error("SendAlertEmail: subject is required");
        }
    }

    #getSubject(environment: string): string {
        const subjectTag = AlertEmailTypeEnum.SUBJECT_TAGS[this.#sendAlertEmailDto.getAlertType()];
        const serverInfo = `${this.#serverIdentifier.getServerName()} (${this.#serverIdentifier.getServerIp()})`;
        return `${subjectTag} (${environment}) ${serverInfo} - ${this.#sendAlertEmailDto.getSubject()}`;
    }

    #getHtmlBody(): string {
        const alertType = this.#sendAlertEmailDto.getAlertType();
        const heading = AlertEmailTypeEnum.HEADINGS[alertType];
        const color = AlertEmailTypeEnum.COLORS[alertType];
        const now = this.#dateTimer.getNowDtIntoTargetTz();
        const baseUrl = this.#htmlEscaper.getEscapedHtml(this.#environmentReaderRawRepository.getBaseUrl());
        const subject = this.#htmlEscaper.getEscapedHtml(this.#sendAlertEmailDto.getSubject());
        const details = this.#htmlEscaper.getEscapedHtml(this.#sendAlertEmailDto.getDetails());

        return `
            <h3 style="color:${color};">[${now}] ${heading}: ${subject}</h3>
            <p><a href="${baseUrl}" target="_blank">${baseUrl}</a></p>
            <pre>${details}</pre>
        `;
    }
}
