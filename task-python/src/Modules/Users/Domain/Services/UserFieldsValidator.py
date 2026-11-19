import re

from Core.config.global_ import __

from Modules.Users.Domain.Enums.UsersKeyEnum import UsersKeyEnum
from Modules.Users.Domain.Enums.UsersMessageEnum import UsersMessageEnum

USER_NAME_PATTERN = re.compile(r"[A-Za-z0-9._-]+")

# el FILTER_VALIDATE_EMAIL de PHP: parte local de atomos separados por puntos y dominio con al menos un punto
USER_EMAIL_PATTERN = re.compile(
    r"[A-Za-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[A-Za-z0-9!#$%&'*+/=?^_`{|}~-]+)*"
    r"@(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?"
)
USER_EMAIL_LOCAL_PART_MAX_LENGTH = 64


class UserFieldsValidator:
    """
    Reglas de escritura de un usuario (alta y modificacion). Devuelve un error "campo: mensaje" por
    cada regla incumplida; vacio = valido.
    """

    # user_name es el identificador de login: sin espacios, unico sin distinguir mayusculas (indice en BD)
    USER_NAME_MIN_LENGTH = 3
    USER_NAME_MAX_LENGTH = 50

    USER_EMAIL_MAX_LENGTH = 150

    # bcrypt solo usa los primeros 72 bytes: mas largo daria por buena una password que no coincide entera
    PASSWORD_MIN_LENGTH = 8
    PASSWORD_MAX_BYTES = 72

    @staticmethod
    def getInstance() -> "UserFieldsValidator":
        return UserFieldsValidator()

    def getErrors(self, userName: str, userEmail: str) -> list[str]:
        errors: list[str] = []
        userNameLength = len(userName)
        if userName == "":
            errors.append(f"{UsersKeyEnum.USER_NAME}: {__(UsersMessageEnum.FIELD_IS_REQUIRED)}")
        elif userNameLength < UserFieldsValidator.USER_NAME_MIN_LENGTH:
            errors.append(f"{UsersKeyEnum.USER_NAME}: {__(UsersMessageEnum.MIN_LENGTH_IS_X, UserFieldsValidator.USER_NAME_MIN_LENGTH)}")
        elif userNameLength > UserFieldsValidator.USER_NAME_MAX_LENGTH:
            errors.append(f"{UsersKeyEnum.USER_NAME}: {__(UsersMessageEnum.MAX_LENGTH_IS_X, UserFieldsValidator.USER_NAME_MAX_LENGTH)}")
        elif not USER_NAME_PATTERN.fullmatch(userName):
            errors.append(f"{UsersKeyEnum.USER_NAME}: {__(UsersMessageEnum.ONLY_LETTERS_DIGITS_DOT_UNDERSCORE_HYPHEN)}")

        if userEmail == "":
            errors.append(f"{UsersKeyEnum.USER_EMAIL}: {__(UsersMessageEnum.FIELD_IS_REQUIRED)}")
        elif len(userEmail) > UserFieldsValidator.USER_EMAIL_MAX_LENGTH:
            errors.append(f"{UsersKeyEnum.USER_EMAIL}: {__(UsersMessageEnum.MAX_LENGTH_IS_X, UserFieldsValidator.USER_EMAIL_MAX_LENGTH)}")
        elif not self._isValidEmail(userEmail):
            errors.append(f"{UsersKeyEnum.USER_EMAIL}: {__(UsersMessageEnum.INVALID_EMAIL)}")
        return errors

    def getPasswordErrors(self, password: str) -> list[str]:
        """
        La password llega con trim (como todo string de un dto) tanto en el alta como en el login: coinciden.
        """
        if password == "":
            return [f"{UsersKeyEnum.PASSWORD}: {__(UsersMessageEnum.FIELD_IS_REQUIRED)}"]
        if len(password) < UserFieldsValidator.PASSWORD_MIN_LENGTH:
            return [f"{UsersKeyEnum.PASSWORD}: {__(UsersMessageEnum.MIN_LENGTH_IS_X, UserFieldsValidator.PASSWORD_MIN_LENGTH)}"]
        if len(password.encode("utf-8")) > UserFieldsValidator.PASSWORD_MAX_BYTES:
            return [f"{UsersKeyEnum.PASSWORD}: {__(UsersMessageEnum.MAX_LENGTH_IS_X, UserFieldsValidator.PASSWORD_MAX_BYTES)}"]
        return []

    def _isValidEmail(self, userEmail: str) -> bool:
        localPart = userEmail.split("@")[0]
        return len(localPart) <= USER_EMAIL_LOCAL_PART_MAX_LENGTH and USER_EMAIL_PATTERN.fullmatch(userEmail) is not None
