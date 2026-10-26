use crate::modules::shared::application::primitives::{Primitives, to_string};

#[derive(Debug, Clone, Default)]
pub struct RunMigrationsResultDto {
    /// traza de lo ocurrido, en orden: la imprime el command (el servicio no escribe en stdout)
    messages: Vec<String>,
}

impl RunMigrationsResultDto {
    pub fn from_primitives(primitives: &Primitives) -> Self {
        let messages = primitives
            .get("messages")
            .and_then(|messages| messages.as_array())
            .map(|messages| messages.iter().map(|message| to_string(message).trim().to_string()).collect())
            .unwrap_or_default();
        Self { messages }
    }

    pub fn get_messages(&self) -> &[String] {
        &self.messages
    }
}
