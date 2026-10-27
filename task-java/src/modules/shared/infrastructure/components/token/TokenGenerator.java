package modules.shared.infrastructure.components.token;

import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.security.SecureRandom;
import java.util.HexFormat;

/**
 * Tokens de API opacos: 32 bytes del CSPRNG en hex (256 bits). Con esa entropia basta un SHA-256 para
 * guardarlos (no hace falta un hash lento tipo bcrypt, que es para contraseñas elegidas por humanos).
 */
public final class TokenGenerator {

    private static final int TOKEN_BYTES = 32;
    private static final String HASH_ALGORITHM = "SHA-256";
    private static final SecureRandom SECURE_RANDOM = new SecureRandom();

    public static TokenGenerator getInstance() {
        return new TokenGenerator();
    }

    public String getNewToken() {
        byte[] tokenBytes = new byte[TOKEN_BYTES];
        SECURE_RANDOM.nextBytes(tokenBytes);
        return HexFormat.of().formatHex(tokenBytes);
    }

    public String getTokenHash(String token) {
        try {
            byte[] hash = MessageDigest.getInstance(HASH_ALGORITHM).digest(token.getBytes(StandardCharsets.UTF_8));
            return HexFormat.of().formatHex(hash);
        }
        catch (NoSuchAlgorithmException e) {
            // SHA-256 es obligatorio en toda JVM
            throw new IllegalStateException(e);
        }
    }
}
