package runmigrations

import "strings"

type RunMigrationsResultDto struct {
	// traza de lo ocurrido, en orden: la imprime el command (el servicio no escribe en stdout)
	messages []string
}

func RunMigrationsResultDtoFromPrimitives(primitives map[string]any) *RunMigrationsResultDto {
	messages, _ := primitives["messages"].([]string)
	trimmedMessages := []string{}
	for _, message := range messages {
		trimmedMessages = append(trimmedMessages, strings.TrimSpace(message))
	}
	return &RunMigrationsResultDto{messages: trimmedMessages}
}

func (d *RunMigrationsResultDto) GetMessages() []string {
	return d.messages
}
