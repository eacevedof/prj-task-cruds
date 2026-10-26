package logger

import "syscall/js"

// Logger el console.error de task-js: lo inesperado se ve en la consola del navegador con su contexto.
type Logger struct{}

func GetInstance() *Logger {
	return &Logger{}
}

// LogError context: "Clase.metodo" de quien lo captura (ListTasksController.invoke).
func (l *Logger) LogError(context string, err error) {
	js.Global().Get("console").Call("error", context, err.Error())
}
