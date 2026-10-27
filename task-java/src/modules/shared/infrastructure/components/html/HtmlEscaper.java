package modules.shared.infrastructure.components.html;

/**
 * El htmlspecialchars() de PHP (ENT_QUOTES): lo que se pinta dentro de un html sin que se interprete como html.
 */
public final class HtmlEscaper {

    public static HtmlEscaper getInstance() {
        return new HtmlEscaper();
    }

    public String getEscapedHtml(String text) {
        StringBuilder escaped = new StringBuilder(text.length());
        for (char character : text.toCharArray()) {
            switch (character) {
                case '&' -> escaped.append("&amp;");
                case '<' -> escaped.append("&lt;");
                case '>' -> escaped.append("&gt;");
                case '"' -> escaped.append("&quot;");
                case '\'' -> escaped.append("&#039;");
                default -> escaped.append(character);
            }
        }
        return escaped.toString();
    }
}
