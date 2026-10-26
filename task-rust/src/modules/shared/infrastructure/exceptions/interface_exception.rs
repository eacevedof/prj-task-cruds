use std::any::Any;
use std::error::Error;

/// el Throwable de PHP: todo error que escala por la app (los controlados y los inesperados de infraestructura, como
/// un fallo de SQLite). Los catch por tipo de task-php son downcast_ref::<UsersException>() sobre el.
pub type AppError = Box<dyn Error + Send + Sync>;

pub type AppResult<T> = Result<T, AppError>;

/// lo que tienen en comun las excepciones controladas (FieldsException, UsersException, TasksException): un mensaje
/// para el cliente (Display) y el codigo HTTP equivalente. Es el getMessage()/getCode() de PHP.
pub trait InterfaceException: Error + Send + Sync {
    fn get_code(&self) -> i64;
}

/// un panic (el error que nadie ha tratado, lo que pasa a panic!) como AppError, para loguearlo igual que una
/// excepcion.
pub fn get_panic_error(panic_payload: &(dyn Any + Send)) -> AppError {
    let message = panic_payload
        .downcast_ref::<String>()
        .cloned()
        .or_else(|| panic_payload.downcast_ref::<&str>().map(|message| message.to_string()))
        .unwrap_or_else(|| "panic".to_string());
    message.into()
}
