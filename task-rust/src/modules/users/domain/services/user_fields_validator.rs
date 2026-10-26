use std::sync::LazyLock;

use regex::Regex;

use crate::core::config::tr;
use crate::modules::users::domain::enums::users_key_enum::UsersKeyEnum;
use crate::modules::users::domain::enums::users_message_enum::UsersMessageEnum;

/// user_name es el identificador de login: sin espacios, unico sin distinguir mayusculas (indice en BD)
pub const USER_NAME_MIN_LENGTH: usize = 3;
pub const USER_NAME_MAX_LENGTH: usize = 50;

pub const USER_EMAIL_MAX_LENGTH: usize = 150;

/// bcrypt solo usa los primeros 72 bytes: mas largo daria por buena una password que no coincide entera
pub const PASSWORD_MIN_LENGTH: usize = 8;
pub const PASSWORD_MAX_BYTES: usize = 72;

static USER_NAME_PATTERN: LazyLock<Regex> = LazyLock::new(|| Regex::new(r"^[A-Za-z0-9._-]+$").unwrap());

/// el FILTER_VALIDATE_EMAIL de PHP: parte local de atomos separados por puntos y dominio con al menos un punto
static USER_EMAIL_PATTERN: LazyLock<Regex> = LazyLock::new(|| {
    Regex::new(concat!(
        r"^[A-Za-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[A-Za-z0-9!#$%&'*+/=?^_`{|}~-]+)*",
        r"@(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?$",
    ))
    .unwrap()
});

/// reglas de escritura de un usuario (alta y modificacion). Devuelve un error "campo: mensaje" por cada regla
/// incumplida; vacio = valido.
pub struct UserFieldsValidator;

impl UserFieldsValidator {
    pub fn get_instance() -> Self {
        Self
    }

    pub fn get_errors(&self, user_name: &str, user_email: &str) -> Vec<String> {
        let mut errors = vec![];
        let user_name_length = user_name.chars().count();
        if user_name.is_empty() {
            errors.push(format!("{}: {}", UsersKeyEnum::USER_NAME, tr(UsersMessageEnum::FIELD_IS_REQUIRED, &[])));
        } else if user_name_length < USER_NAME_MIN_LENGTH {
            errors.push(format!(
                "{}: {}",
                UsersKeyEnum::USER_NAME,
                tr(UsersMessageEnum::MIN_LENGTH_IS_X, &[&USER_NAME_MIN_LENGTH])
            ));
        } else if user_name_length > USER_NAME_MAX_LENGTH {
            errors.push(format!(
                "{}: {}",
                UsersKeyEnum::USER_NAME,
                tr(UsersMessageEnum::MAX_LENGTH_IS_X, &[&USER_NAME_MAX_LENGTH])
            ));
        } else if !USER_NAME_PATTERN.is_match(user_name) {
            errors.push(format!(
                "{}: {}",
                UsersKeyEnum::USER_NAME,
                tr(UsersMessageEnum::ONLY_LETTERS_DIGITS_DOT_UNDERSCORE_HYPHEN, &[])
            ));
        }

        if user_email.is_empty() {
            errors.push(format!("{}: {}", UsersKeyEnum::USER_EMAIL, tr(UsersMessageEnum::FIELD_IS_REQUIRED, &[])));
        } else if user_email.chars().count() > USER_EMAIL_MAX_LENGTH {
            errors.push(format!(
                "{}: {}",
                UsersKeyEnum::USER_EMAIL,
                tr(UsersMessageEnum::MAX_LENGTH_IS_X, &[&USER_EMAIL_MAX_LENGTH])
            ));
        } else if !self.is_valid_email(user_email) {
            errors.push(format!("{}: {}", UsersKeyEnum::USER_EMAIL, tr(UsersMessageEnum::INVALID_EMAIL, &[])));
        }
        errors
    }

    /// la password llega con trim (como todo string de un dto) tanto en el alta como en el login: coinciden.
    pub fn get_password_errors(&self, password: &str) -> Vec<String> {
        if password.is_empty() {
            return vec![format!("{}: {}", UsersKeyEnum::PASSWORD, tr(UsersMessageEnum::FIELD_IS_REQUIRED, &[]))];
        }
        if password.chars().count() < PASSWORD_MIN_LENGTH {
            return vec![format!(
                "{}: {}",
                UsersKeyEnum::PASSWORD,
                tr(UsersMessageEnum::MIN_LENGTH_IS_X, &[&PASSWORD_MIN_LENGTH])
            )];
        }
        if password.len() > PASSWORD_MAX_BYTES {
            return vec![format!(
                "{}: {}",
                UsersKeyEnum::PASSWORD,
                tr(UsersMessageEnum::MAX_LENGTH_IS_X, &[&PASSWORD_MAX_BYTES])
            )];
        }
        vec![]
    }

    fn is_valid_email(&self, user_email: &str) -> bool {
        let local_part = user_email.split('@').next().unwrap_or("");
        local_part.len() <= 64 && USER_EMAIL_PATTERN.is_match(user_email)
    }
}
