package configuration

import (
	"os"
	"strconv"
	"strings"
	"sync"

	"task-go/src/core/boot"
	"task-go/src/modules/shared/domain/enums"
)

// version publicada en /api/v1: es la del CONTRATO (la misma que task-php). Al subirla, anotar el
// cambio en changelog.md
const (
	APP_VERSION        = "2.0.1"
	APP_VERSION_UPDATE = "2026-10-29"
)

// EnvironmentReaderRawRepository fuente UNICA de lectura de entorno: un getter semantico por cada clave del .env
// que la app consume (claves en AppEnvKeyEnum). El .env lo carga AppbootLoadenv() (core/config) en el entorno del
// proceso antes del primer GetInstance(); las variables REALES del contenedor/OS ganan sobre el fichero.
type EnvironmentReaderRawRepository struct{}

var (
	instance     *EnvironmentReaderRawRepository
	instanceOnce sync.Once
)

func GetInstance() *EnvironmentReaderRawRepository {
	instanceOnce.Do(func() {
		instance = &EnvironmentReaderRawRepository{}
	})
	return instance
}

func (e *EnvironmentReaderRawRepository) GetAppVersion() string {
	return APP_VERSION
}

func (e *EnvironmentReaderRawRepository) GetAppVersionUpdate() string {
	return APP_VERSION_UPDATE
}

func (e *EnvironmentReaderRawRepository) GetBaseUrl() string {
	return strings.TrimRight(e.get(enums.AppEnvKeyEnum.APP_BASE_URL), "/")
}

func (e *EnvironmentReaderRawRepository) GetEmailTo() string {
	return e.get(enums.AppEnvKeyEnum.APP_EMAIL_TO)
}

func (e *EnvironmentReaderRawRepository) GetEmailFrom() string {
	return e.get(enums.AppEnvKeyEnum.APP_EMAIL_FROM)
}

// GetSendmailPath el sendmail_path del php.ini de task-php: el comando al que FuncMailer entrega el email.
func (e *EnvironmentReaderRawRepository) GetSendmailPath() string {
	return e.get(enums.AppEnvKeyEnum.APP_SENDMAIL_PATH)
}

func (e *EnvironmentReaderRawRepository) GetLogPath() string {
	return e.get(enums.AppEnvKeyEnum.APP_LOG_PATH)
}

func (e *EnvironmentReaderRawRepository) GetAppName() string {
	return e.get(enums.AppEnvKeyEnum.APP_NAME)
}

func (e *EnvironmentReaderRawRepository) GetEnvironment() string {
	return e.get(enums.AppEnvKeyEnum.APP_ENV)
}

func (e *EnvironmentReaderRawRepository) IsProduction() bool {
	return e.GetEnvironment() == boot.ENV.PRODUCTION
}

func (e *EnvironmentReaderRawRepository) IsLocal() bool {
	return e.GetEnvironment() == boot.ENV.LOCAL
}

func (e *EnvironmentReaderRawRepository) IsDev() bool {
	return e.GetEnvironment() == boot.ENV.DEVELOP
}

func (e *EnvironmentReaderRawRepository) IsTest() bool {
	return e.GetEnvironment() == boot.ENV.TEST
}

// IsDebug filter_var(FILTER_VALIDATE_BOOLEAN): "1", "true", "on", "yes".
func (e *EnvironmentReaderRawRepository) IsDebug() bool {
	debug := strings.ToLower(e.get(enums.AppEnvKeyEnum.APP_DEBUG))
	if debug == "on" || debug == "yes" {
		return true
	}
	isDebug, _ := strconv.ParseBool(debug)
	return isDebug
}

func (e *EnvironmentReaderRawRepository) GetColor() string {
	return boot.ENV.COLORS[e.GetEnvironment()]
}

func (e *EnvironmentReaderRawRepository) GetSqlitePath() string {
	return e.get(enums.AppEnvKeyEnum.APP_SQLITE_PATH)
}

func (e *EnvironmentReaderRawRepository) GetDefaultLang() string {
	return e.get(enums.AppEnvKeyEnum.APP_DEFAULT_LANG)
}

func (e *EnvironmentReaderRawRepository) GetDomain() string {
	return e.get(enums.AppEnvKeyEnum.APP_DOMAIN)
}

func (e *EnvironmentReaderRawRepository) get(key string) string {
	return strings.TrimSpace(os.Getenv(key))
}
