use chrono::NaiveDate;

use crate::core::config::tr;
use crate::modules::tasks::domain::entities::task_entity::TaskEntity;
use crate::modules::tasks::domain::enums::task_status_enum::TaskStatusEnum;
use crate::modules::tasks::domain::enums::task_type_enum::TaskTypeEnum;
use crate::modules::tasks::domain::enums::tasks_message_enum::TasksMessageEnum;

pub const TASK_TITLE_MAX_LENGTH: usize = 150;
pub const TASK_DESCRIPTION_MAX_LENGTH: usize = 2000;
pub const TARGET_DATE_FORMAT: &str = "%Y-%m-%d";

/// reglas de escritura de una task (alta y modificacion). Recibe la entidad YA con los defaults aplicados
/// (status/type vacios -> DEFAULT). Devuelve un error "campo: mensaje" por regla incumplida.
pub struct TaskFieldsValidator;

impl TaskFieldsValidator {
    pub fn get_instance() -> Self {
        Self
    }

    pub fn get_errors(&self, task_entity: &TaskEntity) -> Vec<String> {
        let mut errors = vec![];
        if task_entity.get_task_title().is_empty() {
            errors.push(format!("task_title: {}", tr(TasksMessageEnum::FIELD_IS_REQUIRED, &[])));
        } else if task_entity.get_task_title().chars().count() > TASK_TITLE_MAX_LENGTH {
            errors.push(format!("task_title: {}", tr(TasksMessageEnum::MAX_LENGTH_IS_X, &[&TASK_TITLE_MAX_LENGTH])));
        }

        if !TaskStatusEnum::VALUES.contains(&task_entity.get_task_status()) {
            errors.push(format!(
                "task_status: {}",
                tr(TasksMessageEnum::ALLOWED_VALUES_ARE_X, &[&TaskStatusEnum::VALUES.join(", ")])
            ));
        }

        if !TaskTypeEnum::VALUES.contains(&task_entity.get_task_type()) {
            errors.push(format!(
                "task_type: {}",
                tr(TasksMessageEnum::ALLOWED_VALUES_ARE_X, &[&TaskTypeEnum::VALUES.join(", ")])
            ));
        }

        if !task_entity.get_target_date().is_empty() && !is_valid_date(task_entity.get_target_date()) {
            errors.push(format!("target_date: {}", tr(TasksMessageEnum::INVALID_DATE_FORMAT_X, &[&"YYYY-MM-DD"])));
        }

        if task_entity.get_task_description().chars().count() > TASK_DESCRIPTION_MAX_LENGTH {
            errors.push(format!(
                "task_description: {}",
                tr(TasksMessageEnum::MAX_LENGTH_IS_X, &[&TASK_DESCRIPTION_MAX_LENGTH])
            ));
        }
        errors
    }
}

/// fecha real y en formato exacto: "2026-02-30" o "2026-1-5" no valen.
pub fn is_valid_date(date: &str) -> bool {
    NaiveDate::parse_from_str(date, TARGET_DATE_FORMAT)
        .is_ok_and(|parsed_date| parsed_date.format(TARGET_DATE_FORMAT).to_string() == date)
}
