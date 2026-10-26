// Consola de task-go: el console/console.php de task-php. go run ./cmd/console help
package main

import (
	"fmt"
	"os"

	"task-go/console"
	"task-go/src/core/config"
)

func main() {
	if err := config.AppbootLoadenv(); err != nil {
		fmt.Fprintln(os.Stderr, err)
		os.Exit(2)
	}
	ConsoleMainGetInstance(argvWithArgv0(), console.GetCommands()).Exec()
}
