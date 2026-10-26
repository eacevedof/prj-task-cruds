/// catalogo de entornos. El COMPORTAMIENTO (get_environment/is_local/is_production/is_debug/color) vive en
/// EnvironmentReaderRawRepository (shared/infrastructure/repositories/configuration).
#[allow(non_camel_case_types)]
pub struct ENV;

impl ENV {
    pub const LOCAL: &'static str = "local";
    pub const DEVELOP: &'static str = "develop";
    pub const TEST: &'static str = "test";
    pub const PRODUCTION: &'static str = "production";
    pub const COLORS: [(&'static str, &'static str); 4] = [
        ("local", "#fc0ac3"),   //rosa
        ("develop", "#0174ee"), //azul
        ("test", "#2f8a01"),    //verde
        ("production", ""),
    ];
}
