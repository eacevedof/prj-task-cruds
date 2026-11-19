import re
import threading
import time
import uuid

_UUID_V4_PATTERN = re.compile(r"^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$")

# el ultimo uniqid dado: dos llamadas en el mismo microsegundo no pueden repetirlo (ni dos hilos a la vez)
_lastUniqueIdMicros = 0
_lock = threading.Lock()


class UuidGenerator:
    """
    UUID v4 (RFC 4122) de los *_uuid publicos y el uniqid() de PHP con prefijo ("user-", "task-"). Formato estandar
    para que todas las replicas (Python, Go, Rust, JS...) generen y validen lo mismo.
    """

    @staticmethod
    def getInstance() -> "UuidGenerator":
        return UuidGenerator()

    def getUuidV4(self) -> str:
        return str(uuid.uuid4())

    def getUniqueIdWithPrefix(self, prefix: str) -> str:
        """
        prefix + uniqid(): 8 hex de los segundos + 5 hex de los microsegundos (13 caracteres).
        """
        global _lastUniqueIdMicros
        with _lock:
            micros = time.time_ns() // 1000
            if micros <= _lastUniqueIdMicros:
                micros = _lastUniqueIdMicros + 1
            _lastUniqueIdMicros = micros

        seconds = format(micros // 1_000_000, "x").zfill(8)
        microseconds = format(micros % 1_000_000, "x").zfill(5)
        return f"{prefix}{seconds}{microseconds}"

    def isUuidV4(self, uuidValue: str) -> bool:
        return bool(_UUID_V4_PATTERN.match(uuidValue))
