import { writeSync } from "node:fs";

import type { HttpResponder } from "#app/Modules/Shared/Infrastructure/Components/HttpResponder/HttpResponder.ts";

const STDERR = 2;

/**
 * Lectura del envelope de la API: {"code": int, "status": bool, "errors": string[], "data": object}
 */
export class JsonResponseRepositoryMock {

    #responseArray: { code?: number; status?: boolean; errors?: string[]; data?: Record<string, any> };

    constructor(responseContent: string) {
        this.#responseArray = JSON.parse(responseContent) ?? {};
    }

    static fromPrimitives(responseContent: string): JsonResponseRepositoryMock {
        return new JsonResponseRepositoryMock(responseContent);
    }

    static fromHttpResponse(httpResponse: HttpResponder): JsonResponseRepositoryMock {
        return new JsonResponseRepositoryMock(httpResponse.getBody());
    }

    getCode(): number {
        return Number(this.#responseArray.code ?? 0);
    }

    getStatus(): boolean {
        return Boolean(this.#responseArray.status ?? false);
    }

    getErrors(): string[] {
        return this.#responseArray.errors ?? [];
    }

    getData(): Record<string, any> {
        return this.#responseArray.data ?? {};
    }

    getDebug(useCase: string = ""): void {
        let debug = useCase ? `useCase: ${useCase}, ` : "";
        debug += `code: ${this.getCode()}, status: ${this.getStatus()}, errors: ${this.getErrors().join(" | ")}`;
        writeSync(STDERR, `${debug}\n`);
    }

    resetAll(): void {
        this.#responseArray = {};
    }
}
