import hashlib
import secrets

_TOKEN_BYTES = 32


class TokenGenerator:
    """
    Tokens de API opacos: 32 bytes del CSPRNG en hex (256 bits). Con esa entropia basta un SHA-256 para
    guardarlos (no hace falta un hash lento tipo bcrypt, que es para contraseñas elegidas por humanos).
    """

    @staticmethod
    def getInstance() -> "TokenGenerator":
        return TokenGenerator()

    def getNewToken(self) -> str:
        return secrets.token_hex(_TOKEN_BYTES)

    def getTokenHash(self, token: str) -> str:
        return hashlib.sha256(token.encode("utf-8")).hexdigest()
