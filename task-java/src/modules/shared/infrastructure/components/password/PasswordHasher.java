package modules.shared.infrastructure.components.password;

import java.util.regex.Pattern;

import at.favre.lib.crypto.bcrypt.BCrypt;
import at.favre.lib.crypto.bcrypt.LongPasswordStrategies;

/**
 * Contraseñas elegidas por humanos: hash lento con sal (bcrypt), al contrario que los api_token (TokenGenerator),
 * que son aleatorios y les basta un SHA-256.
 */
public final class PasswordHasher {

    // coste del PASSWORD_DEFAULT de PHP 8.4+ (bcrypt 12): un hash de task-php y uno de task-java se verifican entre si
    private static final int PASSWORD_HASH_COST = 12;

    /**
     * Hash de 32 bytes aleatorios ya descartados (nadie conoce la contraseña): se verifica contra el cuando el
     * usuario no existe o no tiene contraseña, para que la respuesta tarde lo mismo y no delate que user_name existen.
     */
    private static final String DUMMY_HASH = "$2y$12$kUEK9r0sqApY2hnb2ZtJIe4JTyxNGppqF0iNfU7ThCu6i/.FMhrHi";

    private static final Pattern BCRYPT_PREFIX_PATTERN = Pattern.compile("^\\$2[aby]\\$");

    public static PasswordHasher getInstance() {
        return new PasswordHasher();
    }

    /**
     * Con el prefijo $2y$ de PHP.
     */
    public String getPasswordHash(String password) {
        return BCrypt.with(BCrypt.Version.VERSION_2Y, LongPasswordStrategies.truncate(BCrypt.Version.VERSION_2Y))
            .hashToString(PASSWORD_HASH_COST, password.toCharArray());
    }

    /**
     * passwordHash "" = sin contraseña guardada: compara contra el hash ficticio y devuelve false
     */
    public boolean isPasswordValid(String password, String passwordHash) {
        if (passwordHash.isEmpty()) {
            isVerified(password, DUMMY_HASH);
            return false;
        }
        return isVerified(password, passwordHash);
    }

    /**
     * true si el hash no es bcrypt o se hizo con otro coste: se rehace en el siguiente login correcto. $2y$ (PHP),
     * $2a$ (Go) y $2b$ son el mismo bcrypt: no se rehace por el prefijo.
     */
    public boolean doesPasswordHashNeedRehash(String passwordHash) {
        if (!BCRYPT_PREFIX_PATTERN.matcher(passwordHash).find()) {
            return true;
        }
        String[] hashParts = passwordHash.split("\\$");
        return hashParts.length < 3 || !hashParts[2].equals(String.valueOf(PASSWORD_HASH_COST));
    }

    /**
     * Un hash mal formado no lanza: no verifica.
     */
    private boolean isVerified(String password, String passwordHash) {
        try {
            return BCrypt.verifyer(null, LongPasswordStrategies.truncate(BCrypt.Version.VERSION_2Y))
                .verify(password.toCharArray(), passwordHash.toCharArray())
                .verified;
        }
        catch (IllegalArgumentException e) {
            return false;
        }
    }
}
