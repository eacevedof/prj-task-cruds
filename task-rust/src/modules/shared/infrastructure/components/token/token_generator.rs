use sha2::{Digest, Sha256};

const TOKEN_BYTES: usize = 32;

/// tokens de API opacos: 32 bytes del CSPRNG en hex (256 bits). Con esa entropia basta un SHA-256 para guardarlos (no
/// hace falta un hash lento tipo bcrypt, que es para contraseñas elegidas por humanos).
pub struct TokenGenerator;

impl TokenGenerator {
    pub fn get_instance() -> Self {
        Self
    }

    pub fn get_new_token(&self) -> String {
        let mut random_bytes = [0u8; TOKEN_BYTES];
        getrandom::fill(&mut random_bytes).expect("TokenGenerator: the OS random generator failed");
        get_hex(&random_bytes)
    }

    pub fn get_token_hash(&self, token: &str) -> String {
        get_hex(&Sha256::digest(token.as_bytes()))
    }
}

/// bin2hex(): cada byte en dos hex en minusculas.
pub fn get_hex(bytes: &[u8]) -> String {
    bytes.iter().map(|byte| format!("{byte:02x}")).collect()
}
