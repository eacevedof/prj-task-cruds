use std::sync::{LazyLock, Mutex};
use std::time::{SystemTime, UNIX_EPOCH};

use regex::Regex;

use crate::modules::shared::infrastructure::components::token::token_generator::get_hex;

static UUID_V4_PATTERN: LazyLock<Regex> =
    LazyLock::new(|| Regex::new(r"^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$").unwrap());

/// el ultimo uniqid dado: dos llamadas en el mismo microsegundo no pueden repetirlo
static LAST_UNIQUE_ID_MICROS: Mutex<i64> = Mutex::new(0);

/// identificadores publicos: UUID v4 (RFC 4122) y el uniqid() de PHP con prefijo ("user-", "task-"), para que todas
/// las replicas generen y validen lo mismo.
pub struct UuidGenerator;

impl UuidGenerator {
    pub fn get_instance() -> Self {
        Self
    }

    pub fn get_uuid_v4(&self) -> String {
        let mut random_bytes = [0u8; 16];
        getrandom::fill(&mut random_bytes).expect("UuidGenerator: the OS random generator failed");
        random_bytes[6] = (random_bytes[6] & 0x0f) | 0x40;
        random_bytes[8] = (random_bytes[8] & 0x3f) | 0x80;
        let hex_bytes = get_hex(&random_bytes);
        format!(
            "{}-{}-{}-{}-{}",
            &hex_bytes[0..8],
            &hex_bytes[8..12],
            &hex_bytes[12..16],
            &hex_bytes[16..20],
            &hex_bytes[20..32]
        )
    }

    /// prefix + uniqid(): 8 hex de los segundos + 5 hex de los microsegundos (13 caracteres).
    pub fn get_unique_id_with_prefix(&self, prefix: &str) -> String {
        let mut last_unique_id_micros = LAST_UNIQUE_ID_MICROS.lock().unwrap_or_else(|poisoned| poisoned.into_inner());
        let mut micros = SystemTime::now().duration_since(UNIX_EPOCH).map(|now| now.as_micros() as i64).unwrap_or(0);
        if micros <= *last_unique_id_micros {
            micros = *last_unique_id_micros + 1;
        }
        *last_unique_id_micros = micros;
        format!("{prefix}{:08x}{:05x}", micros / 1_000_000, micros % 1_000_000)
    }

    pub fn is_uuid_v4(&self, uuid: &str) -> bool {
        UUID_V4_PATTERN.is_match(uuid)
    }
}
