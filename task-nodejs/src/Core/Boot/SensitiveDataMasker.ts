import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

const MASK = "***";

const SENSITIVE_KEY_PARTS = [
    "password",
    "secret",
    "csrf",
    "token",
    "antibot",
];

export class SensitiveDataMasker {

    static getInstance(): SensitiveDataMasker {
        return new SensitiveDataMasker();
    }

    getMaskedArray(data: Primitives): Primitives {
        const masked: Primitives = {};
        for (const [key, value] of Object.entries(data)) {
            if (this.#isSensitiveKey(key)) {
                masked[key] = MASK;
                continue;
            }
            masked[key] = value !== null && typeof value === "object" ? this.getMaskedArray(value as Primitives) : value;
        }
        return masked;
    }

    #isSensitiveKey(key: string): boolean {
        key = key.toLowerCase();
        return SENSITIVE_KEY_PARTS.some((sensitiveKeyPart) => key.includes(sensitiveKeyPart));
    }
}
