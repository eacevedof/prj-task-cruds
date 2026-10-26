use std::env;
use std::fmt::Display;
use std::fs;
use std::path::Path;
use std::sync::LazyLock;

use regex::Regex;

use crate::core::boot::boot_paths_enum::BOOT_PATHS_ENUM;
use crate::modules::shared::domain::enums::app_env_key_enum::AppEnvKeyEnum;
use crate::modules::shared::infrastructure::components::logger::logger::Logger;

static QUOTED_ENV_VALUE_PATTERN: LazyLock<Regex> = LazyLock::new(|| Regex::new(r#"^(["'])(.*)"#).unwrap());
static ENV_COMMENT_PATTERN: LazyLock<Regex> = LazyLock::new(|| Regex::new(r"(^|\s+)#.*$").unwrap());

/// carga <root>/.env y FALLA RUIDOSO si tras cargarlo falta alguna clave obligatoria. Sin .env la app arrancaria
/// degradada en silencio. Las variables REALES del contenedor/OS cuentan: sin fichero pero con las claves en el
/// entorno, arranca.
/// Se llama al arrancar, antes de lanzar ningun hilo (set_var no es seguro con otros hilos leyendo el entorno).
pub fn appboot_loadenv() -> Result<(), String> {
    let path_env = BOOT_PATHS_ENUM.PATH_ROOT.join(".env");
    appboot_loadenvfile(&path_env);
    appboot_assertrequiredenvkeys(&path_env)
}

/// storage/logs/YYYY-MM-DD-<type>.log (mismo esquema que LogTrait/Logger).
pub fn lgerr(content: &str, title: &str, log_type: &str) {
    let content =
        if title.is_empty() { format!("(lgerr)\n{content}") } else { format!("(lgerr) <<  {title} >>\n{content}") };
    Logger::get_instance().write_log(&format!("{log_type}.log"), &content, &BOOT_PATHS_ENUM.PATH_LOGS);
}

/// el __() de task-php: sustituye {0}, {1}... por los args. El idioma de alli sale de $_REQUEST["lang"], que la API
/// nunca rellena (y no hay src/Core/locale): siempre responde en ingles, el msgid tal cual. Aqui igual.
pub fn tr(msg_id: &str, args: &[&dyn Display]) -> String {
    let mut msg_changed = msg_id.trim().to_string();
    if msg_changed.is_empty() {
        return msg_changed;
    }
    for (index, arg) in args.iter().enumerate() {
        msg_changed = msg_changed.replace(&format!("{{{index}}}"), &arg.to_string());
    }
    msg_changed
}

fn appboot_loadenvfile(path_env: &Path) {
    let Ok(env_content) = fs::read_to_string(path_env) else {
        return;
    };

    let path_public = BOOT_PATHS_ENUM.PATH_PUBLIC.to_string_lossy().replace('\\', "/");
    let path_root = BOOT_PATHS_ENUM.PATH_ROOT.to_string_lossy().replace('\\', "/");
    let path_src = BOOT_PATHS_ENUM.PATH_SRC.to_string_lossy().replace('\\', "/");

    for line in env_content.lines() {
        if line.trim().starts_with('#') || !line.contains('=') {
            continue;
        }

        let (name, value) = line.split_once('=').unwrap_or((line, ""));
        let name = name.trim();
        if name.is_empty() {
            continue;
        }

        // la variable de entorno REAL (contenedor/OS) gana sobre el fichero .env
        if env::var_os(name).is_some() {
            continue;
        }
        let value = appboot_getenvfilevalue(value)
            .replace("%PATH_PUBLIC%", &path_public)
            .replace("%PATH_ROOT%", &path_root)
            .replace("%PATH_SRC%", &path_src);
        // SAFETY: se carga al arrancar el proceso, antes de lanzar ningun hilo
        unsafe { env::set_var(name, value) };
    }
}

/// valor tal cual lo escribiria dotenv: entre comillas ("x" o 'x') se toma el interior literal; sin comillas se corta
/// el comentario en linea (" # ..." o un valor que empieza por "#").
fn appboot_getenvfilevalue(raw_value: &str) -> String {
    let value = raw_value.trim();
    if let Some(captures) = QUOTED_ENV_VALUE_PATTERN.captures(value) {
        // equivale a /^(["'])(.*)\1/: el interior hasta la ULTIMA comilla igual a la de apertura
        let (quote, inner) = (&captures[1], &captures[2]);
        if let Some(closing_index) = inner.rfind(quote) {
            return inner[..closing_index].to_string();
        }
    }
    ENV_COMMENT_PATTERN.replace_all(value, "").trim().to_string()
}

fn appboot_assertrequiredenvkeys(path_env: &Path) -> Result<(), String> {
    let missing_keys = appboot_getmissingenvkeys();
    if missing_keys.is_empty() {
        return Ok(());
    }

    let reason = if path_env.is_file() {
        "the .env file lacks them".to_string()
    } else {
        format!("missing file {} (copy .env.example to .env)", path_env.display())
    };
    Err(format!("appboot_loadenv: required env keys not set ({}): {reason}", missing_keys.join(", ")))
}

/// claves obligatorias ausentes o vacias.
fn appboot_getmissingenvkeys() -> Vec<&'static str> {
    [AppEnvKeyEnum::APP_ENV, AppEnvKeyEnum::APP_DOMAIN, AppEnvKeyEnum::APP_SQLITE_PATH]
        .into_iter()
        .filter(|key| env::var(key).unwrap_or_default().trim().is_empty())
        .collect()
}
