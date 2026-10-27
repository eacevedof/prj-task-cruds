import { randomBytes } from "node:crypto";

const UUID_V4_PATTERN = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/;

// el ultimo uniqid dado: dos llamadas en el mismo microsegundo no pueden repetirlo
let lastUniqueIdMicros = 0;

/**
 * UUID v4 (RFC 4122) de los *_uuid publicos y el uniqid() de PHP con prefijo ("user-", "task-"). Formato estandar
 * para que todas las replicas (Python, Go, Rust, JS...) generen y validen lo mismo.
 */
export class UuidGenerator {

    static getInstance(): UuidGenerator {
        return new UuidGenerator();
    }

    getUuidV4(): string {
        const bytes = randomBytes(16);
        bytes[6] = (bytes[6] & 0x0f) | 0x40;
        bytes[8] = (bytes[8] & 0x3f) | 0x80;
        const hex = bytes.toString("hex");
        return [
            hex.substring(0, 8),
            hex.substring(8, 12),
            hex.substring(12, 16),
            hex.substring(16, 20),
            hex.substring(20, 32),
        ].join("-");
    }

    /**
     * prefix + uniqid(): 8 hex de los segundos + 5 hex de los microsegundos (13 caracteres).
     */
    getUniqueIdWithPrefix(prefix: string): string {
        let micros = Math.floor((performance.timeOrigin + performance.now()) * 1000);
        if (micros <= lastUniqueIdMicros) {
            micros = lastUniqueIdMicros + 1;
        }
        lastUniqueIdMicros = micros;

        const seconds = Math.floor(micros / 1_000_000).toString(16).padStart(8, "0");
        const microseconds = (micros % 1_000_000).toString(16).padStart(5, "0");
        return `${prefix}${seconds}${microseconds}`;
    }

    isUuidV4(uuid: string): boolean {
        return UUID_V4_PATTERN.test(uuid);
    }
}
