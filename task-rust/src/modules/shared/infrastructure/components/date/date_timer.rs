use chrono::{NaiveDateTime, TimeZone, Utc};
use chrono_tz::Tz;

use crate::modules::shared::infrastructure::exceptions::interface_exception::AppResult;

pub const TZ_UTC: &str = "UTC";
pub const FORMAT_FULL_DT: &str = "%Y-%m-%d %H:%M:%S";
pub const FORMAT_ONLY_DATE: &str = "%Y-%m-%d";

/// fechas en texto "Y-m-d H:i:s" (el formato de la BD) entre zonas horarias.
/// Sin las de zona por ip de task-php (ip-api.com): alli no las usa nadie.
pub struct DateTimer;

impl DateTimer {
    pub fn get_instance() -> Self {
        Self
    }

    /// de UTC a TZx: "2022-01-03 10:11:22" -> la misma hora en target_tz.
    pub fn get_utc_dt_in_target_tz(&self, utc_dt: &str, target_tz: &str, format: &str) -> AppResult<String> {
        self.get_source_dt_into_target_tz(utc_dt, TZ_UTC, target_tz, format)
    }

    /// de TZx a TZy. Por defecto va de UI => BD (de UTCx a UTC0).
    pub fn get_source_dt_into_target_tz(
        &self,
        source_dt: &str,
        source_tz: &str,
        target_tz: &str,
        format: &str,
    ) -> AppResult<String> {
        let source_location: Tz = source_tz.parse()?;
        let target_location: Tz = target_tz.parse()?;
        let source_naive_dt = NaiveDateTime::parse_from_str(source_dt, FORMAT_FULL_DT)?;
        let source_time = source_location
            .from_local_datetime(&source_naive_dt)
            .earliest()
            .ok_or_else(|| format!("DateTimer: {source_dt} does not exist in {source_tz}"))?;
        Ok(source_time.with_timezone(&target_location).format(format).to_string())
    }

    /// fecha y hora actuales en UTC con formato "Y-m-d H:i:s".
    pub fn get_now_dt_into_target_tz(&self) -> String {
        Utc::now().format(FORMAT_FULL_DT).to_string()
    }
}
