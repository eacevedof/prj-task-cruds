/// un servicio no guarda errores: si falla, escala con su excepcion y ahi acaba. Este trait solo da formato a una pila
/// de errores (p.ej. la de un validador) para meterla en el mensaje de esa excepcion:
/// FieldsException::bad_request_custom(self.get_error_stack_message(&tr(FIELDS_VALIDATION_ERRORS, &[]), &errors))
pub trait ErrorStackTrait {
    /// "Fields validation errors: user_name: field is required; user_email: invalid email"
    fn get_error_stack_message(&self, title: &str, error_stack: &[String]) -> String {
        format!("{title}: {}", error_stack.join("; "))
    }
}
