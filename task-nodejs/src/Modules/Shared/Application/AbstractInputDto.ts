import { LanguageCodeEnum } from "#app/Modules/Shared/Domain/Enums/LanguageCodeEnum.ts";

import { getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

/**
 * Metadata de la peticion que viaja con TODO dto de entrada hasta la logica de negocio (ip, navegador, so,
 * zona horaria, idioma). La rellena el controller (HttpRequester.getMetadata), que es quien lee el transporte: el
 * servicio solo ve su dto. Desde CLI o un test llega vacia y el idioma cae en EN.
 * El dto hijo llama primero a super(primitives).
 */
export abstract class AbstractInputDto {

    readonly #userIpAddress: string;
    readonly #userBrowser: string;
    readonly #userBrowserVersion: string;
    readonly #userOs: string;
    readonly #userTimeZone: string;
    readonly #userLanguageCode: string;

    constructor(primitives: Primitives) {
        this.#userIpAddress = getTrimmedString(primitives.userIpAddress);
        this.#userBrowser = getTrimmedString(primitives.userBrowser);
        this.#userBrowserVersion = getTrimmedString(primitives.userBrowserVersion);
        this.#userOs = getTrimmedString(primitives.userOs);
        this.#userTimeZone = getTrimmedString(primitives.userTimeZone);
        this.#userLanguageCode = getTrimmedString(primitives.userLanguageCode) || LanguageCodeEnum.EN;
    }

    getUserIpAddress(): string {
        return this.#userIpAddress;
    }

    getUserBrowser(): string {
        return this.#userBrowser;
    }

    getUserBrowserVersion(): string {
        return this.#userBrowserVersion;
    }

    getUserOs(): string {
        return this.#userOs;
    }

    getUserTimeZone(): string {
        return this.#userTimeZone;
    }

    getUserLanguageCode(): string {
        return this.#userLanguageCode;
    }
}
