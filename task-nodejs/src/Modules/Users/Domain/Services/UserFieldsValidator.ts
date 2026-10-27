import { __ } from "#app/Core/config/global.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { UsersMessageEnum } from "#app/Modules/Users/Domain/Enums/UsersMessageEnum.ts";

const USER_NAME_PATTERN = /^[A-Za-z0-9._-]+$/;

// el FILTER_VALIDATE_EMAIL de PHP: parte local de atomos separados por puntos y dominio con al menos un punto
const USER_EMAIL_PATTERN = new RegExp(
    "^[A-Za-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\\.[A-Za-z0-9!#$%&'*+/=?^_`{|}~-]+)*"
    + "@(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\\.)+[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?$"
);
const USER_EMAIL_LOCAL_PART_MAX_LENGTH = 64;

/**
 * Reglas de escritura de un usuario (alta y modificacion). Devuelve un error "campo: mensaje" por
 * cada regla incumplida; vacio = valido.
 */
export class UserFieldsValidator {

    // user_name es el identificador de login: sin espacios, unico sin distinguir mayusculas (indice en BD)
    static readonly USER_NAME_MIN_LENGTH = 3;
    static readonly USER_NAME_MAX_LENGTH = 50;

    static readonly USER_EMAIL_MAX_LENGTH = 150;

    // bcrypt solo usa los primeros 72 bytes: mas largo daria por buena una password que no coincide entera
    static readonly PASSWORD_MIN_LENGTH = 8;
    static readonly PASSWORD_MAX_BYTES = 72;

    static getInstance(): UserFieldsValidator {
        return new UserFieldsValidator();
    }

    getErrors(userName: string, userEmail: string): string[] {
        const errors: string[] = [];
        const userNameLength = [...userName].length;
        if (userName === "") {
            errors.push(`${UsersKeyEnum.USER_NAME}: ${__(UsersMessageEnum.FIELD_IS_REQUIRED)}`);
        }
        else if (userNameLength < UserFieldsValidator.USER_NAME_MIN_LENGTH) {
            errors.push(`${UsersKeyEnum.USER_NAME}: ${__(UsersMessageEnum.MIN_LENGTH_IS_X, UserFieldsValidator.USER_NAME_MIN_LENGTH)}`);
        }
        else if (userNameLength > UserFieldsValidator.USER_NAME_MAX_LENGTH) {
            errors.push(`${UsersKeyEnum.USER_NAME}: ${__(UsersMessageEnum.MAX_LENGTH_IS_X, UserFieldsValidator.USER_NAME_MAX_LENGTH)}`);
        }
        else if (!USER_NAME_PATTERN.test(userName)) {
            errors.push(`${UsersKeyEnum.USER_NAME}: ${__(UsersMessageEnum.ONLY_LETTERS_DIGITS_DOT_UNDERSCORE_HYPHEN)}`);
        }

        if (userEmail === "") {
            errors.push(`${UsersKeyEnum.USER_EMAIL}: ${__(UsersMessageEnum.FIELD_IS_REQUIRED)}`);
        }
        else if ([...userEmail].length > UserFieldsValidator.USER_EMAIL_MAX_LENGTH) {
            errors.push(`${UsersKeyEnum.USER_EMAIL}: ${__(UsersMessageEnum.MAX_LENGTH_IS_X, UserFieldsValidator.USER_EMAIL_MAX_LENGTH)}`);
        }
        else if (!this.#isValidEmail(userEmail)) {
            errors.push(`${UsersKeyEnum.USER_EMAIL}: ${__(UsersMessageEnum.INVALID_EMAIL)}`);
        }
        return errors;
    }

    /**
     * La password llega con trim (como todo string de un dto) tanto en el alta como en el login: coinciden.
     */
    getPasswordErrors(password: string): string[] {
        if (password === "") {
            return [`${UsersKeyEnum.PASSWORD}: ${__(UsersMessageEnum.FIELD_IS_REQUIRED)}`];
        }
        if ([...password].length < UserFieldsValidator.PASSWORD_MIN_LENGTH) {
            return [`${UsersKeyEnum.PASSWORD}: ${__(UsersMessageEnum.MIN_LENGTH_IS_X, UserFieldsValidator.PASSWORD_MIN_LENGTH)}`];
        }
        if (Buffer.byteLength(password) > UserFieldsValidator.PASSWORD_MAX_BYTES) {
            return [`${UsersKeyEnum.PASSWORD}: ${__(UsersMessageEnum.MAX_LENGTH_IS_X, UserFieldsValidator.PASSWORD_MAX_BYTES)}`];
        }
        return [];
    }

    #isValidEmail(userEmail: string): boolean {
        const localPart = userEmail.split("@")[0];
        return localPart.length <= USER_EMAIL_LOCAL_PART_MAX_LENGTH && USER_EMAIL_PATTERN.test(userEmail);
    }
}
