use std::fs::{self, OpenOptions};
use std::io::Write;
use std::path::{Path, PathBuf};
use std::sync::{LazyLock, Mutex};

use chrono::Utc;
use regex::Regex;

use crate::modules::shared::infrastructure::repositories::configuration::environment_reader_raw_repository::EnvironmentReaderRawRepository;

/// mismo fichero desde varias peticiones a la vez: las escrituras se serializan
static WRITE_LOG_MUTEX: Mutex<()> = Mutex::new(());

static SQL_MARGIN_PATTERN: LazyLock<Regex> = LazyLock::new(|| Regex::new(r"(?m)^ {8}").unwrap());

/// escribe en <logs_path>/YYYY-MM-DD-<nombre>.<ext> (ext "sql" si el nombre contiene "sql").
pub struct Logger;

impl Logger {
    pub fn get_instance() -> Self {
        Self
    }

    pub fn write_log(&self, file_path: &str, content: &str, logs_path: &Path) {
        let mut logs_path = logs_path.to_path_buf();
        if logs_path.as_os_str().is_empty() {
            logs_path = PathBuf::from(EnvironmentReaderRawRepository::get_instance().get_log_path());
        }
        if logs_path.as_os_str().is_empty() || fs::create_dir_all(&logs_path).is_err() {
            return;
        }

        let log_file_path = logs_path.join(file_path);
        let final_dir = log_file_path.parent().map(Path::to_path_buf).unwrap_or(logs_path);
        let _ = fs::create_dir_all(&final_dir);

        let file_name = log_file_path.file_stem().map(|stem| stem.to_string_lossy().to_string()).unwrap_or_default();
        let mut ext = log_file_path.extension().map(|ext| ext.to_string_lossy().to_string()).unwrap_or_default();
        if ext.is_empty() {
            ext = "log".to_string();
        }
        if file_path.contains("sql") {
            ext = "sql".to_string();
        }

        // la app trabaja en UTC (date_default_timezone_set("UTC") en task-php)
        let now = Utc::now();
        let log_file_path = final_dir.join(format!("{}-{file_name}.{ext}", now.format("%Y-%m-%d")));
        let mut content = content.trim().to_string();
        if ext == "sql" {
            content = self.get_normalized_margin_for_sql(&content);
        }

        let _write_log_lock = WRITE_LOG_MUTEX.lock().unwrap_or_else(|poisoned| poisoned.into_inner());
        let Ok(mut log_file) = OpenOptions::new().create(true).append(true).open(&log_file_path) else {
            return;
        };
        let _ = write!(log_file, "\n[{}]\n{content}", now.format("%Y-%m-%d %H:%M:%S"));
    }

    fn get_normalized_margin_for_sql(&self, content: &str) -> String {
        SQL_MARGIN_PATTERN.replace_all(&format!("{content}\n"), "").to_string()
    }
}
