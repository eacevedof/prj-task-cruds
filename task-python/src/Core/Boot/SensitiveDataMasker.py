_MASK = "***"

_SENSITIVE_KEY_PARTS = [
    "password",
    "secret",
    "csrf",
    "token",
    "antibot",
]


class SensitiveDataMasker:

    @staticmethod
    def getInstance() -> "SensitiveDataMasker":
        return SensitiveDataMasker()

    def getMaskedArray(self, data: dict) -> dict:
        masked: dict = {}
        for key, value in data.items():
            if self._isSensitiveKey(key):
                masked[key] = _MASK
                continue
            masked[key] = self.getMaskedArray(value) if isinstance(value, dict) else value
        return masked

    def _isSensitiveKey(self, key: str) -> bool:
        key = str(key).lower()
        return any(sensitiveKeyPart in key for sensitiveKeyPart in _SENSITIVE_KEY_PARTS)
