/// msgid comunes (los que se usan; el resto de task-php es herencia sin uso).
pub struct SharedMessageEnum;

impl SharedMessageEnum {
    pub const AN_UNEXPECTED_ERROR_HAS_OCCURRED: &'static str = "An unexpected error has occurred";
    pub const FIELDS_VALIDATION_ERRORS: &'static str = "Fields validation errors";
}
