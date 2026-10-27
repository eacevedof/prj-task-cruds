package console.application;

import java.util.List;
import java.util.Map;

import console.Commands;
import console.Commands.CommandConfig;

import modules.shared.infrastructure.commands.AbstractConsoleCommand;

/**
 * bin/console help   (o sin alias)
 * Lista console/Commands: por comando, que hace y la llamada tal cual se escribe (la clase no: es detalle interno).
 * En verde el comando, en amarillo la llamada que se copia.
 */
public final class HelpCommand extends AbstractConsoleCommand {

    public HelpCommand(List<String> input) {
        super(input);
    }

    @Override
    public void run() {
        StringBuilder lines = new StringBuilder();
        int i = 1;
        for (Map.Entry<String, CommandConfig> command : Commands.getCommands().entrySet()) {
            CommandConfig conf = command.getValue();
            String info = conf.info().isEmpty() ? "" : "\n\t" + conf.info();
            String call = ("bin/console " + command.getKey() + " " + conf.example()).trim();
            lines.append("\n(").append(i).append(") ").append(cliColorTrait.getGreen(command.getKey())).append(":")
                .append(info).append("\n\t").append(cliColorTrait.getYellow(call));
            i++;
        }
        cliColorTrait.echoBlue("help menu");
        System.out.println(lines);
        System.out.flush();
    }
}
