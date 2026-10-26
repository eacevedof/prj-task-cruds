use crate::core::config::tr;
use crate::modules::tasks::domain::enums::task_status_enum::TaskStatusEnum;
use crate::modules::tasks::domain::enums::task_type_enum::TaskTypeEnum;
use crate::modules::tasks::domain::enums::tasks_key_enum::TasksKeyEnum;
use crate::modules::tasks::domain::enums::tasks_message_enum::TasksMessageEnum;
use crate::modules::tasks::domain::services::task_fields_validator::is_valid_date;

pub const PAGE_MIN: i64 = 1;
pub const PAGE_SIZE_MIN: i64 = 1;
pub const PAGE_SIZE_MAX: i64 = 100;
pub const PAGE_SIZE_DEFAULT: i64 = 20;

/// reglas de una busqueda paginada de tasks. Filtros vacios = sin filtro. Devuelve un error "campo: mensaje" por
/// regla incumplida; vacio = valida.
pub struct TasksSearchValidator;

impl TasksSearchValidator {
    pub fn get_instance() -> Self {
        Self
    }

    pub fn get_errors(
        &self,
        task_status: &str,
        task_type: &str,
        target_date_from: &str,
        target_date_to: &str,
        page: i64,
        page_size: i64,
    ) -> Vec<String> {
        let mut errors = vec![];
        if !task_status.is_empty() && !TaskStatusEnum::VALUES.contains(&task_status) {
            errors.push(format!(
                "{}: {}",
                TasksKeyEnum::TASK_STATUS,
                tr(TasksMessageEnum::ALLOWED_VALUES_ARE_X, &[&TaskStatusEnum::VALUES.join(", ")])
            ));
        }
        if !task_type.is_empty() && !TaskTypeEnum::VALUES.contains(&task_type) {
            errors.push(format!(
                "{}: {}",
                TasksKeyEnum::TASK_TYPE,
                tr(TasksMessageEnum::ALLOWED_VALUES_ARE_X, &[&TaskTypeEnum::VALUES.join(", ")])
            ));
        }

        let is_target_date_from_valid = target_date_from.is_empty() || is_valid_date(target_date_from);
        if !is_target_date_from_valid {
            errors.push(format!(
                "{}: {}",
                TasksKeyEnum::TARGET_DATE_FROM,
                tr(TasksMessageEnum::INVALID_DATE_FORMAT_X, &[&"YYYY-MM-DD"])
            ));
        }
        let is_target_date_to_valid = target_date_to.is_empty() || is_valid_date(target_date_to);
        if !is_target_date_to_valid {
            errors.push(format!(
                "{}: {}",
                TasksKeyEnum::TARGET_DATE_TO,
                tr(TasksMessageEnum::INVALID_DATE_FORMAT_X, &[&"YYYY-MM-DD"])
            ));
        }
        // fechas ISO validas: comparar el texto es comparar la fecha
        if !target_date_from.is_empty()
            && !target_date_to.is_empty()
            && is_target_date_from_valid
            && is_target_date_to_valid
            && target_date_to < target_date_from
        {
            errors.push(format!(
                "{}: {}",
                TasksKeyEnum::TARGET_DATE_TO,
                tr(TasksMessageEnum::DATE_TO_BEFORE_DATE_FROM, &[])
            ));
        }

        if page < PAGE_MIN {
            errors.push(format!("{}: {}", TasksKeyEnum::PAGE, tr(TasksMessageEnum::MIN_VALUE_IS_X, &[&PAGE_MIN])));
        }
        if !(PAGE_SIZE_MIN..=PAGE_SIZE_MAX).contains(&page_size) {
            errors.push(format!(
                "{}: {}",
                TasksKeyEnum::PAGE_SIZE,
                tr(TasksMessageEnum::VALUE_BETWEEN_X_AND_X, &[&PAGE_SIZE_MIN, &PAGE_SIZE_MAX])
            ));
        }
        errors
    }
}
