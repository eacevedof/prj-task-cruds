package modules.users.domain.services;

import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.List;
import java.util.regex.Pattern;

import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.enums.UsersMessageEnum;

import static core.config.Global.__;

/**
 * Reglas de escritura de un usuario (alta y modificacion). Devuelve un error "campo: mensaje" por
 * cada regla incumplida; vacio = valido.
 */
public final class UserFieldsValidator {

    // user_name es el identificador de login: sin espacios, unico sin distinguir mayusculas (indice en BD)
    public static final int USER_NAME_MIN_LENGTH = 3;
    public static final int USER_NAME_MAX_LENGTH = 50;

    public static final int USER_EMAIL_MAX_LENGTH = 150;

    // bcrypt solo usa los primeros 72 bytes: mas largo daria por buena una password que no coincide entera
    public static final int PASSWORD_MIN_LENGTH = 8;
    public static final int PASSWORD_MAX_BYTES = 72;

    private static final Pattern USER_NAME_PATTERN = Pattern.compile("^[A-Za-z0-9._-]+$");

    // el FILTER_VALIDATE_EMAIL de PHP: parte local de atomos separados por puntos y dominio con al menos un punto
    private static final Pattern USER_EMAIL_PATTERN = Pattern.compile(
        "^[A-Za-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\\.[A-Za-z0-9!#$%&'*+/=?^_`{|}~-]+)*"
        + "@(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\\.)+[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?$"
    );
    private static final int USER_EMAIL_LOCAL_PART_MAX_LENGTH = 64;

    public static UserFieldsValidator getInstance() {
        return new UserFieldsValidator();
    }

    public List<String> getErrors(String userName, String userEmail) {
        List<String> errors = new ArrayList<>();
        int userNameLength = userName.codePointCount(0, userName.length());
        if (userName.isEmpty()) {
            errors.add(UsersKeyEnum.USER_NAME + ": " + __(UsersMessageEnum.FIELD_IS_REQUIRED));
        }
        else if (userNameLength < USER_NAME_MIN_LENGTH) {
            errors.add(UsersKeyEnum.USER_NAME + ": " + __(UsersMessageEnum.MIN_LENGTH_IS_X, USER_NAME_MIN_LENGTH));
        }
        else if (userNameLength > USER_NAME_MAX_LENGTH) {
            errors.add(UsersKeyEnum.USER_NAME + ": " + __(UsersMessageEnum.MAX_LENGTH_IS_X, USER_NAME_MAX_LENGTH));
        }
        else if (!USER_NAME_PATTERN.matcher(userName).matches()) {
            errors.add(UsersKeyEnum.USER_NAME + ": " + __(UsersMessageEnum.ONLY_LETTERS_DIGITS_DOT_UNDERSCORE_HYPHEN));
        }

        if (userEmail.isEmpty()) {
            errors.add(UsersKeyEnum.USER_EMAIL + ": " + __(UsersMessageEnum.FIELD_IS_REQUIRED));
        }
        else if (userEmail.codePointCount(0, userEmail.length()) > USER_EMAIL_MAX_LENGTH) {
            errors.add(UsersKeyEnum.USER_EMAIL + ": " + __(UsersMessageEnum.MAX_LENGTH_IS_X, USER_EMAIL_MAX_LENGTH));
        }
        else if (!isValidEmail(userEmail)) {
            errors.add(UsersKeyEnum.USER_EMAIL + ": " + __(UsersMessageEnum.INVALID_EMAIL));
        }
        return errors;
    }

    /**
     * La password llega con trim (como todo string de un dto) tanto en el alta como en el login: coinciden.
     */
    public List<String> getPasswordErrors(String password) {
        if (password.isEmpty()) {
            return List.of(UsersKeyEnum.PASSWORD + ": " + __(UsersMessageEnum.FIELD_IS_REQUIRED));
        }
        if (password.codePointCount(0, password.length()) < PASSWORD_MIN_LENGTH) {
            return List.of(UsersKeyEnum.PASSWORD + ": " + __(UsersMessageEnum.MIN_LENGTH_IS_X, PASSWORD_MIN_LENGTH));
        }
        if (password.getBytes(StandardCharsets.UTF_8).length > PASSWORD_MAX_BYTES) {
            return List.of(UsersKeyEnum.PASSWORD + ": " + __(UsersMessageEnum.MAX_LENGTH_IS_X, PASSWORD_MAX_BYTES));
        }
        return List.of();
    }

    private boolean isValidEmail(String userEmail) {
        String localPart = userEmail.split("@", -1)[0];
        return localPart.length() <= USER_EMAIL_LOCAL_PART_MAX_LENGTH && USER_EMAIL_PATTERN.matcher(userEmail).matches();
    }
}
