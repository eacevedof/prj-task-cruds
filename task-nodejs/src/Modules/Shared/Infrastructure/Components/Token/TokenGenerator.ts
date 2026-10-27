import { createHash, randomBytes } from "node:crypto";

const TOKEN_BYTES = 32;
const HASH_ALGORITHM = "sha256";

/**
 * Tokens de API opacos: 32 bytes del CSPRNG en hex (256 bits). Con esa entropia basta un SHA-256 para
 * guardarlos (no hace falta un hash lento tipo bcrypt, que es para contraseñas elegidas por humanos).
 */
export class TokenGenerator {

    static getInstance(): TokenGenerator {
        return new TokenGenerator();
    }

    getNewToken(): string {
        return randomBytes(TOKEN_BYTES).toString("hex");
    }

    getTokenHash(token: string): string {
        return createHash(HASH_ALGORITHM).update(token).digest("hex");
    }
}
