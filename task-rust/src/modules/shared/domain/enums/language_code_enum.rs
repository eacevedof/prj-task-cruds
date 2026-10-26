/// idiomas que entiende la API (subetiqueta primaria de Accept-Language, ISO 639-1).
pub struct LanguageCodeEnum;

impl LanguageCodeEnum {
    pub const ES: &'static str = "es";
    pub const EN: &'static str = "en";
    pub const VALUES: [&'static str; 2] = ["es", "en"];
}
