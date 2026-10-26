package main

import (
	"errors"
	"fmt"
	"os"
	"runtime/debug"
	"strings"

	"task-go/src/console/application"
	"task-go/src/core/boot"
	"task-go/src/core/config"
	"task-go/src/modules/shared/infrastructure/components/uuid"
	"task-go/src/modules/shared/infrastructure/repositories/configuration"
)

// ConsoleMain el console.php de task-php: ./console <alias> --clave=valor ... -> el command del alias.
// Sin el modo --class=... de task-php (invocar cualquier clase por reflexion): alli nadie lo usa.
type ConsoleMain struct {
	argv           []string
	commandConfigs []application.CommandConfig
}

func ConsoleMainGetInstance(argv []string, commandConfigs []application.CommandConfig) *ConsoleMain {
	boot.SetSapiNameCli()
	return &ConsoleMain{argv: argv, commandConfigs: commandConfigs}
}

func (m *ConsoleMain) Exec() {
	defer func() {
		if recovered := recover(); recovered != nil {
			err := fmt.Errorf("%v", recovered)
			debugError(err)
			onError(err)
		}
	}()

	if err := m.invokeConsoleServiceWithArgs(); err != nil {
		debugError(err)
		onError(err)
	}
}

func (m *ConsoleMain) invokeConsoleServiceWithArgs() error {
	alias := ""
	if len(m.argv) > 1 {
		alias = strings.TrimSpace(m.argv[1])
	}
	if alias == "" {
		alias = "help"
	}

	for _, commandConfig := range m.commandConfigs {
		if commandConfig.Alias != alias {
			continue
		}
		//a partir de la pos 2 en adelante son parametros de input
		var input []string
		if len(m.argv) > 2 {
			input = m.argv[2:]
		}
		commandConfig.Service(input).Run()
		return nil
	}
	return errors.New("no class found for cmd " + alias)
}

func onError(err error) {
	errorUuid := uuid.GetInstance().GetUniqueIdWithPrefix("")
	config.Lgerr(err.Error(), "console-exception "+errorUuid, "error")
	config.Lgerr(string(debug.Stack()), "console-exception "+errorUuid+" TRACE", "error")
}

func debugError(err error) {
	environmentReaderRawRepository := configuration.GetInstance()
	if environmentReaderRawRepository.IsProduction() || !environmentReaderRawRepository.IsDebug() {
		return
	}
	fmt.Printf("Exception: %s\nresponse: 500\n", err.Error())
}

// argvWithArgv0 os.Args con el alias en la posicion 1, como el $argv de PHP.
func argvWithArgv0() []string {
	if len(os.Args) == 0 {
		return []string{"console"}
	}
	return os.Args
}
