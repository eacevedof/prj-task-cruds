package modules.shared.infrastructure.components.uuid;

import java.time.Instant;
import java.util.UUID;
import java.util.concurrent.atomic.AtomicLong;
import java.util.regex.Pattern;

/**
 * UUID v4 (RFC 4122) de los *_uuid publicos y el uniqid() de PHP con prefijo ("user-", "task-"). Formato estandar
 * para que todas las replicas (Python, Go, Rust, JS...) generen y validen lo mismo.
 */
public final class UuidGenerator {

    private static final Pattern UUID_V4_PATTERN = Pattern.compile(
        "^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$"
    );

    // el ultimo uniqid dado: dos llamadas en el mismo microsegundo (o en dos hilos) no pueden repetirlo
    private static final AtomicLong LAST_UNIQUE_ID_MICROS = new AtomicLong();

    public static UuidGenerator getInstance() {
        return new UuidGenerator();
    }

    /**
     * UUID.randomUUID() ya es la v4 aleatoria (SecureRandom), en minusculas.
     */
    public String getUuidV4() {
        return UUID.randomUUID().toString();
    }

    /**
     * prefix + uniqid(): 8 hex de los segundos + 5 hex de los microsegundos (13 caracteres).
     */
    public String getUniqueIdWithPrefix(String prefix) {
        Instant now = Instant.now();
        long nowMicros = now.getEpochSecond() * 1_000_000L + now.getNano() / 1_000L;
        long micros = LAST_UNIQUE_ID_MICROS.updateAndGet(lastMicros -> Math.max(lastMicros + 1, nowMicros));

        return prefix + String.format("%08x%05x", micros / 1_000_000L, micros % 1_000_000L);
    }

    public boolean isUuidV4(String uuid) {
        return UUID_V4_PATTERN.matcher(uuid).matches();
    }
}
