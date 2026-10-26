package application

import (
	"fmt"
	"strings"

	"task-go/src/modules/shared/infrastructure/commands"
)

// HelpCommand ./console help   (o sin alias)
// Lista console/commands.go: por comando, que hace y la llamada tal cual se escribe (el struct no: es detalle interno).
// En verde el comando, en amarillo la llamada que se copia.
type HelpCommand struct {
	commands.AbstractConsoleCommand
	commandConfigs []CommandConfig
}

func HelpCommandGetInstance(input []string, commandConfigs []CommandConfig) *HelpCommand {
	return &HelpCommand{
		AbstractConsoleCommand: commands.NewAbstractConsoleCommand(input),
		commandConfigs:         commandConfigs,
	}
}

func (c *HelpCommand) Run() {
	var lines []string
	for i, commandConfig := range c.commandConfigs {
		info := ""
		if commandConfig.Info != "" {
			info = "\n\t" + commandConfig.Info
		}
		call := strings.TrimSpace("./console " + commandConfig.Alias + " " + commandConfig.Example)
		lines = append(lines, fmt.Sprintf("\n(%d) ", i+1)+c.GetGreen(commandConfig.Alias)+":"+info+"\n\t"+c.GetYellow(call))
	}
	c.EchoBlue("help menu")
	fmt.Println(strings.Join(lines, ""))
}
