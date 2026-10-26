use serde_json::Value;

use crate::modules::shared::application::primitives::Primitives;

const SENSITIVE_DATA_MASK: &str = "***";

const SENSITIVE_KEY_PARTS: [&str; 5] = ["password", "secret", "csrf", "token", "antibot"];

pub struct SensitiveDataMasker;

impl SensitiveDataMasker {
    pub fn get_instance() -> Self {
        Self
    }

    pub fn get_masked_array(&self, data: &Primitives) -> Primitives {
        let mut masked = Primitives::new();
        for (key, value) in data {
            if self.is_sensitive_key(key) {
                masked.insert(key.clone(), Value::from(SENSITIVE_DATA_MASK));
                continue;
            }
            if let Value::Object(sub_data) = value {
                masked.insert(key.clone(), Value::Object(self.get_masked_array(sub_data)));
                continue;
            }
            masked.insert(key.clone(), value.clone());
        }
        masked
    }

    fn is_sensitive_key(&self, key: &str) -> bool {
        let key = key.to_lowercase();
        SENSITIVE_KEY_PARTS.iter().any(|sensitive_key_part| key.contains(sensitive_key_part))
    }
}
