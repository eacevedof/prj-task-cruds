use bcrypt::Version;

use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;

/// coste del PASSWORD_DEFAULT de PHP 8.4+ (bcrypt 12): un hash de task-php y uno de task-rust se verifican entre si
const PASSWORD_HASH_COST: u32 = 12;

/// hash de 32 bytes aleatorios ya descartados (nadie conoce la contraseña): se verifica contra el cuando el usuario
/// no existe o no tiene contraseña, para que la respuesta tarde lo mismo y no delate que user_name existen.
const DUMMY_HASH: &str = "$2y$12$kUEK9r0sqApY2hnb2ZtJIe4JTyxNGppqF0iNfU7ThCu6i/.FMhrHi";

/// contraseñas elegidas por humanos: hash lento con sal (bcrypt), al contrario que los api_token (TokenGenerator),
/// que son aleatorios y les basta un SHA-256.
pub struct PasswordHasher;

impl PasswordHasher {
    pub fn get_instance() -> Self {
        Self
    }

    /// la password ya viene validada (8-72 bytes): bcrypt no admite mas de 72. Con el prefijo $2y$ de PHP.
    pub fn get_password_hash(&self, password: &str) -> AppResult<String> {
        Ok(bcrypt::hash_with_result(password, PASSWORD_HASH_COST)?.format_for_version(Version::TwoY))
    }

    /// password_hash "" = sin contraseña guardada: compara contra el hash ficticio y devuelve false.
    pub fn is_password_valid(&self, password: &str, password_hash: &str) -> bool {
        if password_hash.is_empty() {
            let _ = bcrypt::verify(password, DUMMY_HASH);
            return false;
        }
        bcrypt::verify(password, password_hash).unwrap_or(false)
    }

    /// true si el hash no es bcrypt o se hizo con otro coste: se rehace en el siguiente login correcto. $2y$ (PHP),
    /// $2a$ (Go) y $2b$ son el mismo bcrypt: no se rehace por el prefijo.
    pub fn does_password_hash_need_rehash(&self, password_hash: &str) -> bool {
        if !password_hash.starts_with("$2") {
            return true;
        }
        let cost = password_hash.split('$').nth(2).and_then(|cost| cost.parse::<u32>().ok());
        cost != Some(PASSWORD_HASH_COST)
    }
}
