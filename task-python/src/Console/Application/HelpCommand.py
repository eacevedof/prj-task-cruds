import sys

from Modules.Shared.Infrastructure.Commands.AbstractConsoleCommand import AbstractConsoleCommand


class HelpCommand(AbstractConsoleCommand):
    """
    python cmd/console/main.py help   (o sin alias)
    Lista console/commands.py: por comando, que hace y la llamada tal cual se escribe (la clase no: es detalle interno).
    En verde el comando, en amarillo la llamada que se copia.
    """

    def run(self) -> None:
        # import tardio: console.commands importa HelpCommand, y un import circular al cargar el modulo fallaria
        from console.commands import getCommands

        lines: list[str] = []
        i = 1
        for command, conf in getCommands().items():
            info = f"\n\t{conf['info']}" if conf["info"] else ""
            call = f"python cmd/console/main.py {command} {conf['example']}".strip()
            lines.append(f"\n({i}) {self.cliColorTrait.getGreen(command)}:{info}\n\t{self.cliColorTrait.getYellow(call)}")
            i += 1
        self.cliColorTrait.echoBlue("help menu")
        sys.stdout.write("".join(lines) + "\n")
        sys.stdout.flush()
