import re

import bcrypt

# coste del PASSWORD_DEFAULT de PHP 8.4+ (bcrypt 12): un hash de task-php y uno de task-python se verifican entre si
_PASSWORD_HASH_COST = 12

# bcrypt solo mira los 72 primeros bytes (PHP los trunca en silencio; la lib de Python 5.x lanza ValueError)
_BCRYPT_MAX_BYTES = 72

# Hash de 32 bytes aleatorios ya descartados (nadie conoce la contraseña): se verifica contra el cuando el
# usuario no existe o no tiene contraseña, para que la respuesta tarde lo mismo y no delate que user_name existen.
_DUMMY_HASH = "$2y$12$kUEK9r0sqApY2hnb2ZtJIe4JTyxNGppqF0iNfU7ThCu6i/.FMhrHi"


class PasswordHasher:
    """
    Contraseñas elegidas por humanos: hash lento con sal (bcrypt), al contrario que los api_token (TokenGenerator),
    que son aleatorios y les basta un SHA-256.
    """

    @staticmethod
    def getInstance() -> "PasswordHasher":
        return PasswordHasher()

    def getPasswordHash(self, password: str) -> str:
        """
        Con el prefijo $2y$ de PHP: la lib da $2b$, que es el mismo bcrypt.
        """
        passwordHash = bcrypt.hashpw(self._getBytes(password), bcrypt.gensalt(_PASSWORD_HASH_COST)).decode("ascii")
        return re.sub(r"^\$2b\$", "$2y$", passwordHash)

    def isPasswordValid(self, password: str, passwordHash: str) -> bool:
        """
        passwordHash "" = sin contraseña guardada: compara contra el hash ficticio y devuelve False
        """
        if not passwordHash:
            self._check(password, _DUMMY_HASH)
            return False
        return self._check(password, passwordHash)

    def doesPasswordHashNeedRehash(self, passwordHash: str) -> bool:
        """
        True si el hash no es bcrypt o se hizo con otro coste: se rehace en el siguiente login correcto. $2y$ (PHP),
        $2a$ (Go) y $2b$ son el mismo bcrypt: no se rehace por el prefijo.
        """
        if not re.match(r"^\$2[aby]\$", passwordHash):
            return True
        return int(passwordHash.split("$")[2]) != _PASSWORD_HASH_COST

    def _check(self, password: str, passwordHash: str) -> bool:
        # la lib acepta $2a$/$2b$/$2x$/$2y$; se normaliza a $2b$ por si una version no admitiera el de PHP
        normalizedHash = re.sub(r"^\$2[ay]\$", "$2b$", passwordHash)
        try:
            return bcrypt.checkpw(self._getBytes(password), normalizedHash.encode("ascii"))
        except ValueError:
            return False

    def _getBytes(self, password: str) -> bytes:
        return password.encode("utf-8")[:_BCRYPT_MAX_BYTES]
