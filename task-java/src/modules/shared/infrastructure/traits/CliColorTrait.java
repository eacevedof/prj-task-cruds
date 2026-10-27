package modules.shared.infrastructure.traits;

/**
 * Colores ANSI para la consola (copia de app-plataformabase): getXxx() devuelve el texto coloreado para
 * componer una linea, echoXxx() la imprime entera por stdout. Sin dieRed: el codigo de salida lo pone
 * AbstractConsoleCommand (1 dominio, 2 infraestructura).
 */
public final class CliColorTrait {

    public static CliColorTrait getInstance() {
        return new CliColorTrait();
    }

    public void echoRed(String text) {
        echo(getRed(text));
    }

    public String getRed(String text) {
        return "\u001b[91m" + text + "\u001b[0m";
    }

    public void echoGreen(String text) {
        echo(getGreen(text));
    }

    public String getGreen(String text) {
        return "\u001b[92m" + text + "\u001b[0m";
    }

    public void echoYellow(String text) {
        echo(getYellow(text));
    }

    public String getYellow(String text) {
        return "\u001b[93m" + text + "\u001b[0m";
    }

    public void echoBlue(String text) {
        echo(getBlue(text));
    }

    public String getBlue(String text) {
        return "\u001b[94m" + text + "\u001b[0m";
    }

    public void echoWhite(String text) {
        echo(getWhite(text));
    }

    public String getWhite(String text) {
        return "\u001b[97m" + text + "\u001b[0m";
    }

    public void echoOrange(String text) {
        echo(getOrange(text));
    }

    public String getOrange(String text) {
        return "\u001b[38;5;214m" + text + "\u001b[0m";
    }

    private void echo(String line) {
        System.out.println(line);
        System.out.flush();
    }
}
