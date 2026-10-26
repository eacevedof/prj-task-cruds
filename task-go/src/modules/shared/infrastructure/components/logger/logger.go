package logger

import (
	"os"
	"path/filepath"
	"regexp"
	"strings"
	"sync"
	"time"

	"task-go/src/modules/shared/infrastructure/repositories/configuration"
)

// mismo fichero desde varias peticiones a la vez: las escrituras se serializan
var writeLogMutex sync.Mutex

var sqlMarginPattern = regexp.MustCompile(`(?m)^ {8}`)

// Logger escribe en <logsPath>/YYYY-MM-DD-<nombre>.<ext> (ext "sql" si el nombre contiene "sql").
type Logger struct{}

func GetInstance() *Logger {
	return &Logger{}
}

func (l *Logger) WriteLog(filePath string, content string, logsPath string) {
	if logsPath == "" {
		logsPath = configuration.GetInstance().GetLogPath()
	}
	if logsPath == "" {
		return
	}
	if err := os.MkdirAll(logsPath, 0o777); err != nil {
		return
	}

	logsFolderPath, err := filepath.Abs(logsPath)
	if err != nil {
		return
	}
	logFilePath := filepath.Join(logsFolderPath, filePath)
	finalDir := filepath.Dir(logFilePath)
	_ = os.MkdirAll(finalDir, 0o777)

	ext := strings.TrimPrefix(filepath.Ext(logFilePath), ".")
	fileName := strings.TrimSuffix(filepath.Base(logFilePath), filepath.Ext(logFilePath))
	if ext == "" {
		ext = "log"
	}
	if strings.Contains(filePath, "sql") {
		ext = "sql"
	}

	// la app trabaja en UTC (date_default_timezone_set("UTC") en task-php)
	now := time.Now().UTC()
	logFilePath = filepath.Join(finalDir, now.Format("2006-01-02")+"-"+fileName+"."+ext)
	content = strings.TrimSpace(content)
	if ext == "sql" {
		content = l.getNormalizedMarginForSql(content)
	}

	writeLogMutex.Lock()
	defer writeLogMutex.Unlock()
	_, statErr := os.Stat(logFilePath)
	isNewFile := os.IsNotExist(statErr)
	logFile, err := os.OpenFile(logFilePath, os.O_APPEND|os.O_CREATE|os.O_WRONLY, 0o666)
	if err != nil {
		return
	}
	defer logFile.Close()
	_, _ = logFile.WriteString("\n[" + now.Format("2006-01-02 15:04:05") + "]\n" + content)
	if isNewFile {
		_ = os.Chmod(logFilePath, 0o666)
	}
}

func (l *Logger) getNormalizedMarginForSql(content string) string {
	return sqlMarginPattern.ReplaceAllString(content+"\n", "")
}
