package modules.shared.infrastructure.components.markdown;

import java.util.ArrayList;
import java.util.Arrays;
import java.util.List;
import java.util.Locale;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import modules.shared.infrastructure.components.html.HtmlEscaper;

/**
 * Markdown -> HTML sin vendor (port del Markdowner de app-ms-antiphising). Cubre lo que usa la documentacion:
 * titulos (con id para enlazar #anclas), negrita/cursiva/tachado, enlaces, imagenes, citas, <hr>, listas,
 * tablas GFM y codigo (en bloque y en linea, escapado). El resto del texto NO se escapa: el .md es nuestro.
 */
public final class Markdowner {

    private static final String CODE_BLOCK_PLACEHOLDER = "XZCODEBLOCKX%dXPLACEHOLDERX";
    private static final String INLINE_CODE_PLACEHOLDER = "XZINLINECODEX%dXPLACEHOLDERX";
    private static final List<String> BLOCK_TAG_PREFIXES = List.of("<h", "<ul>", "<ol>", "<pre>", "<blockquote>", "<hr>", "<table");

    private static final Pattern CODE_BLOCK_PATTERN = Pattern.compile("```(\\w*)\\n?([\\s\\S]*?)```");
    private static final Pattern INLINE_CODE_PATTERN = Pattern.compile("`([^`\\n]+)`");
    private static final Pattern IMG_PATTERN = Pattern.compile("!\\[([^\\]]*)\\]\\(([^)]+)\\)");
    private static final Pattern A_PATTERN = Pattern.compile("\\[([^\\]]+)\\]\\(([^)]+)\\)");
    private static final Pattern H_PATTERN = Pattern.compile("(?m)^(#{1,6})\\s+(.*)$");
    private static final Pattern BLOCKQUOTE_PATTERN = Pattern.compile("(?m)^> (.*)$");
    private static final Pattern HR_PATTERN = Pattern.compile("(?m)^(---|\\*\\*\\*|___)\\s*$");
    private static final Pattern UL_ITEM_PATTERN = Pattern.compile("^\\s*[-*+]\\s+(.*)$");
    private static final Pattern OL_ITEM_PATTERN = Pattern.compile("^\\s*\\d+\\.\\s+(.*)$");
    private static final Pattern TABLE_SEPARATOR_PATTERN = Pattern.compile("^\\|[\\s:|-]+\\|$");

    // patron => reemplazo, en este orden
    private static final List<Pattern> TEXT_FORMATTING_PATTERNS = List.of(
        Pattern.compile("\\*\\*\\*([^*\\n]+)\\*\\*\\*"),
        Pattern.compile("___([^_\\n]+)___"),
        Pattern.compile("\\*\\*([^*\\n]+)\\*\\*"),
        Pattern.compile("(?<!_)__([^_\\n]+)__(?!_)"),
        Pattern.compile("(?<!\\*)\\*([^*\\n]+)\\*(?!\\*)"),
        // cursiva con _ solo entre espacios/bordes: "user_uuid" o "api_token" no son cursiva
        Pattern.compile("(?<![\\w_])_([^_\\n]+)_(?![\\w_])"),
        Pattern.compile("~~([^~\\n]+)~~")
    );
    private static final List<String> TEXT_FORMATTING_REPLACEMENTS = List.of(
        "<strong><em>$1</em></strong>",
        "<strong><em>$1</em></strong>",
        "<strong>$1</strong>",
        "<strong>$1</strong>",
        "<em>$1</em>",
        "<em>$1</em>",
        "<del>$1</del>"
    );

    private List<String> preAndCodeBlocks = new ArrayList<>();
    private List<String> inlineCodeBlocks = new ArrayList<>();

    public static Markdowner getInstance() {
        return new Markdowner();
    }

    public String getHtmlFromMarkdown(String markdown) {
        preAndCodeBlocks = new ArrayList<>();
        inlineCodeBlocks = new ArrayList<>();

        // un checkout en Windows (autocrlf) deja CRLF y los patrones de linea ($) no casarian
        String html = getWithCodePlaceholders(markdown.replace("\r\n", "\n"));
        // imagenes antes que enlaces: "![alt](src)" contiene un "[alt](src)"
        html = getImgTags(html);
        html = getATags(html);
        html = getHTags(html);
        html = getTextFormattingTags(html);
        html = getBlockquoteTags(html);
        html = getHrTags(html);
        html = getListTags(html);
        // el codigo se restaura antes de tablas y parrafos: puede ir dentro de una celda
        html = getWithCodeRestored(html);
        html = getTableTags(html);
        return getPTags(html);
    }

    /**
     * El codigo se aparta con un placeholder para que ninguna otra regla toque su contenido.
     */
    private String getWithCodePlaceholders(String markdown) {
        markdown = CODE_BLOCK_PATTERN.matcher(markdown).replaceAll(matchResult -> {
            String language = matchResult.group(1);
            String languageClass = language.isEmpty() ? "" : " class=\"language-" + language + "\"";
            preAndCodeBlocks.add("<pre><code" + languageClass + ">" + getEscapedHtml(matchResult.group(2)) + "</code></pre>");
            return Matcher.quoteReplacement(getPlaceholder(CODE_BLOCK_PLACEHOLDER, preAndCodeBlocks.size() - 1));
        });

        return INLINE_CODE_PATTERN.matcher(markdown).replaceAll(matchResult -> {
            inlineCodeBlocks.add("<code>" + getEscapedHtml(matchResult.group(1)) + "</code>");
            return Matcher.quoteReplacement(getPlaceholder(INLINE_CODE_PLACEHOLDER, inlineCodeBlocks.size() - 1));
        });
    }

    private String getPlaceholder(String placeholder, int index) {
        return placeholder.replace("%d", String.valueOf(index));
    }

    private String getEscapedHtml(String code) {
        return HtmlEscaper.getInstance().getEscapedHtml(code);
    }

    private String getImgTags(String html) {
        return IMG_PATTERN.matcher(html).replaceAll("<img alt=\"$1\" src=\"$2\">");
    }

    private String getATags(String html) {
        return A_PATTERN.matcher(html).replaceAll("<a href=\"$2\">$1</a>");
    }

    private String getHTags(String html) {
        return H_PATTERN.matcher(html).replaceAll(matchResult -> {
            int level = matchResult.group(1).length();
            String title = matchResult.group(2);
            return Matcher.quoteReplacement("<h" + level + " id=\"" + getAnchorId(title) + "\">" + title + "</h" + level + ">");
        });
    }

    /**
     * "2.1 Alta de usuario" -> "2-1-alta-de-usuario"; los puntos de una version se mantienen ("1.0.0").
     */
    private String getAnchorId(String title) {
        String anchorId = title.replaceAll("<[^>]*>", "").toLowerCase(Locale.ROOT);
        anchorId = anchorId.replaceAll("[^\\p{L}\\p{N}.]+", "-");
        return anchorId.replaceAll("^[-.]+|[-.]+$", "");
    }

    private String getTextFormattingTags(String html) {
        for (int i = 0; i < TEXT_FORMATTING_PATTERNS.size(); i++) {
            html = TEXT_FORMATTING_PATTERNS.get(i).matcher(html).replaceAll(TEXT_FORMATTING_REPLACEMENTS.get(i));
        }
        return html;
    }

    private String getBlockquoteTags(String html) {
        return BLOCKQUOTE_PATTERN.matcher(html).replaceAll("<blockquote>$1</blockquote>");
    }

    private String getHrTags(String html) {
        return HR_PATTERN.matcher(html).replaceAll("<hr>");
    }

    private String getListTags(String html) {
        List<String> listLines = new ArrayList<>();
        String openListTag = "";
        for (String line : html.split("\n", -1)) {
            String listTag = "";
            Matcher matcher = UL_ITEM_PATTERN.matcher(line);
            boolean isListItem = matcher.matches();
            if (isListItem) {
                listTag = "ul";
            }
            else {
                matcher = OL_ITEM_PATTERN.matcher(line);
                isListItem = matcher.matches();
                if (isListItem) listTag = "ol";
            }

            if (!openListTag.isEmpty() && !listTag.equals(openListTag)) {
                listLines.add("</" + openListTag + ">");
                openListTag = "";
            }
            if (!isListItem) {
                if (!line.trim().isEmpty()) listLines.add(line);
                continue;
            }
            if (openListTag.isEmpty()) {
                listLines.add("<" + listTag + ">");
                openListTag = listTag;
            }
            listLines.add("<li>" + matcher.group(1) + "</li>");
        }
        if (!openListTag.isEmpty()) listLines.add("</" + openListTag + ">");

        return String.join("\n", listLines);
    }

    private String getWithCodeRestored(String html) {
        for (int i = 0; i < inlineCodeBlocks.size(); i++) {
            html = html.replace(getPlaceholder(INLINE_CODE_PLACEHOLDER, i), inlineCodeBlocks.get(i));
        }
        for (int i = 0; i < preAndCodeBlocks.size(); i++) {
            html = html.replace(getPlaceholder(CODE_BLOCK_PLACEHOLDER, i), preAndCodeBlocks.get(i));
        }
        return html;
    }

    /**
     * Tabla = fila "| a | b |" seguida de una separadora "|---|:--:|" (que fija la alineacion).
     */
    private String getTableTags(String html) {
        String[] lines = html.split("\n", -1);
        List<String> tableLines = new ArrayList<>();
        int i = 0;
        while (i < lines.length) {
            String line = lines[i].trim();
            boolean isTableStart = line.startsWith("|")
                && i + 1 < lines.length
                && TABLE_SEPARATOR_PATTERN.matcher(lines[i + 1].trim()).matches();
            if (!isTableStart) {
                tableLines.add(lines[i]);
                i++;
                continue;
            }

            List<String> alignments = getTableAlignments(lines[i + 1].trim());
            StringBuilder tableHtml = new StringBuilder("<table class=\"md-table\">\n<thead>\n<tr>")
                .append(getTableCells(line, "th", alignments))
                .append("</tr>\n</thead>\n<tbody>");
            i += 2;
            while (i < lines.length && lines[i].trim().startsWith("|")) {
                tableHtml.append("\n<tr>").append(getTableCells(lines[i].trim(), "td", alignments)).append("</tr>");
                i++;
            }
            tableLines.add(tableHtml.append("\n</tbody>\n</table>").toString());
        }
        return String.join("\n", tableLines);
    }

    private String getTableCells(String row, String cellTag, List<String> alignments) {
        StringBuilder cellsHtml = new StringBuilder();
        List<String> cells = getTableRowCells(row);
        for (int index = 0; index < cells.size(); index++) {
            String alignment = index < alignments.size() ? alignments.get(index) : "";
            String style = alignment.isEmpty() ? "" : " style=\"text-align:" + alignment + "\"";
            cellsHtml.append("<").append(cellTag).append(style).append(">")
                .append(cells.get(index).trim())
                .append("</").append(cellTag).append(">");
        }
        return cellsHtml.toString();
    }

    private List<String> getTableRowCells(String row) {
        return Arrays.asList(row.trim().replaceAll("^\\|+|\\|+$", "").split("\\|", -1));
    }

    private List<String> getTableAlignments(String separatorRow) {
        List<String> alignments = new ArrayList<>();
        for (String cell : getTableRowCells(separatorRow)) {
            cell = cell.trim();
            boolean isLeft = cell.startsWith(":");
            boolean isRight = cell.endsWith(":");
            if (isLeft && isRight) alignments.add("center");
            else if (isRight) alignments.add("right");
            else if (isLeft) alignments.add("left");
            else alignments.add("");
        }
        return alignments;
    }

    private String getPTags(String html) {
        List<String> paragraphs = new ArrayList<>();
        for (String paragraph : html.replaceAll("\n\n+", "\n\n").split("\n\n")) {
            if (paragraph.trim().isEmpty()) continue;
            paragraphs.add(getParagraphHtml(paragraph.trim()));
        }
        return String.join("\n", paragraphs);
    }

    private String getParagraphHtml(String paragraph) {
        for (String blockTagPrefix : BLOCK_TAG_PREFIXES) {
            if (paragraph.startsWith(blockTagPrefix)) return paragraph;
        }
        if (paragraph.contains("<table") || paragraph.contains("<pre><code")) return paragraph;

        return "<p>" + paragraph.replace("\n", "<br>") + "</p>";
    }
}
