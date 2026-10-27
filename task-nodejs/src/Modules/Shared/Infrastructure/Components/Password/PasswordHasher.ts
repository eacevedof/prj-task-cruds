import bcrypt from "bcryptjs";

// coste del PASSWORD_DEFAULT de PHP 8.4+ (bcrypt 12): un hash de task-php y uno de task-nodejs se verifican entre si
const PASSWORD_HASH_COST = 12;

/**
 * Hash de 32 bytes aleatorios ya descartados (nadie conoce la contraseña): se verifica contra el cuando el
 * usuario no existe o no tiene contraseña, para que la respuesta tarde lo mismo y no delate que user_name existen.
 */
const DUMMY_HASH = "$2y$12$kUEK9r0sqApY2hnb2ZtJIe4JTyxNGppqF0iNfU7ThCu6i/.FMhrHi";

/**
 * Contraseñas elegidas por humanos: hash lento con sal (bcrypt), al contrario que los api_token (TokenGenerator),
 * que son aleatorios y les basta un SHA-256.
 */
export class PasswordHasher {

    static getInstance(): PasswordHasher {
        return new PasswordHasher();
    }

    /**
     * Con el prefijo $2y$ de PHP: bcryptjs da $2b$, que es el mismo bcrypt.
     */
    getPasswordHash(password: string): string {
        return bcrypt.hashSync(password, PASSWORD_HASH_COST).replace(/^\$2b\$/, "$2y$");
    }

    /**
     * passwordHash "" = sin contraseña guardada: compara contra el hash ficticio y devuelve false
     */
    isPasswordValid(password: string, passwordHash: string): boolean {
        if (!passwordHash) {
            bcrypt.compareSync(password, DUMMY_HASH);
            return false;
        }
        return bcrypt.compareSync(password, passwordHash);
    }

    /**
     * true si el hash no es bcrypt o se hizo con otro coste: se rehace en el siguiente login correcto. $2y$ (PHP),
     * $2a$ (Go) y $2b$ son el mismo bcrypt: no se rehace por el prefijo.
     */
    doesPasswordHashNeedRehash(passwordHash: string): boolean {
        if (!/^\$2[aby]\$/.test(passwordHash)) {
            return true;
        }
        return Number(passwordHash.split("$")[2]) !== PASSWORD_HASH_COST;
    }
}
