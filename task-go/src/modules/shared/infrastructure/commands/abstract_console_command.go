package commands

import (
	"bytes"
	"encoding/json"
	"fmt"
	"os"
	"strings"

	"task-go/src/core/config"
	"task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/exceptions"
	"task-go/src/modules/shared/infrastructure/traits"
)

const (
	EXIT_CODE_DOMAIN_ERROR         = 1
	EXIT_CODE_INFRASTRUCTURE_ERROR = 2
)

// AbstractConsoleCommand el AbstractHttpController de la consola (vault: CLI Commands as Controllers): lee
// --clave=valor, imprime el ResultDto en JSON por stdout (sin color: se puede pasar a jq) y los errores en rojo por
// stderr con su codigo de salida.
// 0 ok · 1 error de dominio (lo que en HTTP es un 4xx) · 2 error de infraestructura (el 500, se loguea).
type AbstractConsoleCommand struct {
	traits.CliColorTrait
	traits.LogTrait
	traits.MailTrait
	// los argumentos que van tras el alias del comando
	input []string
}

func NewAbstractConsoleCommand(input []string) AbstractConsoleCommand {
	logTrait := traits.NewLogTrait(nil)
	return AbstractConsoleCommand{
		LogTrait:  logTrait,
		MailTrait: traits.NewMailTrait(logTrait),
		input:     input,
	}
}

// GetOptionValue --user_name="Ana Lopez" -> "Ana Lopez". Si no se pasa, "" (el servicio decide si es obligatorio).
func (c *AbstractConsoleCommand) GetOptionValue(optionName string) string {
	optionPrefix := "--" + optionName + "="
	for _, argument := range c.input {
		if strings.HasPrefix(argument, optionPrefix) {
			return argument[len(optionPrefix):]
		}
	}
	return ""
}

// PrintResult JSON_PRETTY_PRINT (4 espacios) sin escapar unicode ni barras.
func (c *AbstractConsoleCommand) PrintResult(result any) {
	var jsonBuffer bytes.Buffer
	jsonEncoder := json.NewEncoder(&jsonBuffer)
	jsonEncoder.SetEscapeHTML(false)
	jsonEncoder.SetIndent("", "    ")
	_ = jsonEncoder.Encode(result)
	fmt.Print(jsonBuffer.String())
}

func (c *AbstractConsoleCommand) ExitWithDomainError(ex exceptions.InterfaceException) {
	c.printErrors(ex.GetCode(), []string{ex.Error()})
	os.Exit(EXIT_CODE_DOMAIN_ERROR)
}

// ExitWithUnexpectedError como el 500 de la API: se loguea con el metodo que fallo y se avisa por email. A diferencia
// de la API, quien tiene la consola si ve el mensaje real (LogDebugCli).
func (c *AbstractConsoleCommand) ExitWithUnexpectedError(err error, logTitle string) {
	c.LogException(err, logTitle)
	c.LogDebugCli(err.Error(), logTitle)
	c.SendMailOnThrowable(err, logTitle)
	c.printErrors(
		enums.ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
		[]string{config.Tr(enums.SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED)},
	)
	os.Exit(EXIT_CODE_INFRASTRUCTURE_ERROR)
}

// printErrors code el mismo que daria la API (404, 409, 400...), para que consola y HTTP se lean igual.
func (c *AbstractConsoleCommand) printErrors(code int, errors []string) {
	fmt.Fprintln(os.Stderr, c.GetRed(fmt.Sprintf("Error (%d):", code)))
	for _, errorMessage := range errors {
		fmt.Fprintln(os.Stderr, c.GetRed("  - "+errorMessage))
	}
}
