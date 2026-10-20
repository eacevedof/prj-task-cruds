<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Components\Markdown;

/**
 * Markdown -> HTML sin vendor (port del Markdowner de app-ms-antiphising). Cubre lo que usa la documentacion:
 * titulos (con id para enlazar #anclas), negrita/cursiva/tachado, enlaces, imagenes, citas, <hr>, listas,
 * tablas GFM y codigo (en bloque y en linea, escapado). El resto del texto NO se escapa: el .md es nuestro.
 */
final class Markdowner
{
    private const string CODE_BLOCK_PLACEHOLDER = "XZCODEBLOCKX%dXPLACEHOLDERX";
    private const string INLINE_CODE_PLACEHOLDER = "XZINLINECODEX%dXPLACEHOLDERX";
    private const array BLOCK_TAG_PREFIXES = ["<h", "<ul>", "<ol>", "<pre>", "<blockquote>", "<hr>", "<table"];

    private array $preAndCodeBlocks = [];
    private array $inlineCodeBlocks = [];

    public static function getInstance(): self
    {
        return new self();
    }

    public function getHtmlFromMarkdown(string $markdown): string
    {
        $this->preAndCodeBlocks = [];
        $this->inlineCodeBlocks = [];

        // un checkout en Windows (autocrlf) deja CRLF y los patrones de linea ($) no casarian
        $html = $this->getWithCodePlaceholders(str_replace("\r\n", "\n", $markdown));
        // imagenes antes que enlaces: "![alt](src)" contiene un "[alt](src)"
        $html = $this->getImgTags($html);
        $html = $this->getATags($html);
        $html = $this->getHTags($html);
        $html = $this->getTextFormattingTags($html);
        $html = $this->getBlockquoteTags($html);
        $html = $this->getHrTags($html);
        $html = $this->getListTags($html);
        // el codigo se restaura antes de tablas y parrafos: puede ir dentro de una celda
        $html = $this->getWithCodeRestored($html);
        $html = $this->getTableTags($html);
        return $this->getPTags($html);
    }

    /**
     * El codigo se aparta con un placeholder para que ninguna otra regla toque su contenido.
     */
    private function getWithCodePlaceholders(string $markdown): string
    {
        $markdown = (string) preg_replace_callback(
            "/```(\w*)\n?([\s\S]*?)```/",
            function (array $matches): string {
                $language = $matches[1] ? " class=\"language-{$matches[1]}\"" : "";
                $this->preAndCodeBlocks[] = "<pre><code{$language}>{$this->getEscapedHtml($matches[2])}</code></pre>";
                return sprintf(self::CODE_BLOCK_PLACEHOLDER, count($this->preAndCodeBlocks) - 1);
            },
            $markdown
        );

        return (string) preg_replace_callback(
            "/`([^`\n]+)`/",
            function (array $matches): string {
                $this->inlineCodeBlocks[] = "<code>{$this->getEscapedHtml($matches[1])}</code>";
                return sprintf(self::INLINE_CODE_PLACEHOLDER, count($this->inlineCodeBlocks) - 1);
            },
            $markdown
        );
    }

    private function getEscapedHtml(string $code): string
    {
        return htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
    }

    private function getImgTags(string $html): string
    {
        return (string) preg_replace("/!\[([^\]]*)\]\(([^)]+)\)/", "<img alt=\"$1\" src=\"$2\">", $html);
    }

    private function getATags(string $html): string
    {
        return (string) preg_replace("/\[([^\]]+)\]\(([^)]+)\)/", "<a href=\"$2\">$1</a>", $html);
    }

    private function getHTags(string $html): string
    {
        return (string) preg_replace_callback(
            "/^(#{1,6})\s+(.*)$/m",
            function (array $matches): string {
                $level = strlen($matches[1]);
                return "<h{$level} id=\"{$this->getAnchorId($matches[2])}\">{$matches[2]}</h{$level}>";
            },
            $html
        );
    }

    /**
     * "2.1 Alta de usuario" -> "2-1-alta-de-usuario"; los puntos de una version se mantienen ("1.0.0").
     */
    private function getAnchorId(string $title): string
    {
        $anchorId = mb_strtolower(strip_tags($title));
        $anchorId = (string) preg_replace("/[^\p{L}\p{N}.]+/u", "-", $anchorId);
        return trim($anchorId, "-.");
    }

    private function getTextFormattingTags(string $html): string
    {
        $patterns = [
            "/\*\*\*([^*\n]+)\*\*\*/" => "<strong><em>$1</em></strong>",
            "/___([^_\n]+)___/" => "<strong><em>$1</em></strong>",
            "/\*\*([^*\n]+)\*\*/" => "<strong>$1</strong>",
            "/(?<!_)__([^_\n]+)__(?!_)/" => "<strong>$1</strong>",
            "/(?<!\*)\*([^*\n]+)\*(?!\*)/" => "<em>$1</em>",
            // cursiva con _ solo entre espacios/bordes: "user_uuid" o "api_token" no son cursiva
            "/(?<![\w_])_([^_\n]+)_(?![\w_])/" => "<em>$1</em>",
            "/~~([^~\n]+)~~/" => "<del>$1</del>",
        ];
        return (string) preg_replace(array_keys($patterns), array_values($patterns), $html);
    }

    private function getBlockquoteTags(string $html): string
    {
        return (string) preg_replace("/^> (.*)$/m", "<blockquote>$1</blockquote>", $html);
    }

    private function getHrTags(string $html): string
    {
        return (string) preg_replace("/^(---|\*\*\*|___)\s*$/m", "<hr>", $html);
    }

    private function getListTags(string $html): string
    {
        $listLines = [];
        $openListTag = "";
        foreach (explode("\n", $html) as $line) {
            $listTag = match (true) {
                (bool) preg_match("/^\s*[-*+]\s+(.*)$/", $line, $matches) => "ul",
                (bool) preg_match("/^\s*\d+\.\s+(.*)$/", $line, $matches) => "ol",
                default => "",
            };

            if ($openListTag && $listTag !== $openListTag) {
                $listLines[] = "</{$openListTag}>";
                $openListTag = "";
            }
            if (!$listTag) {
                if (trim($line) !== "") $listLines[] = $line;
                continue;
            }
            if (!$openListTag) {
                $listLines[] = "<{$listTag}>";
                $openListTag = $listTag;
            }
            $listLines[] = "<li>{$matches[1]}</li>";
        }
        if ($openListTag) $listLines[] = "</{$openListTag}>";

        return implode("\n", $listLines);
    }

    private function getWithCodeRestored(string $html): string
    {
        foreach ($this->inlineCodeBlocks as $index => $inlineCodeBlock) {
            $html = str_replace(sprintf(self::INLINE_CODE_PLACEHOLDER, $index), $inlineCodeBlock, $html);
        }
        foreach ($this->preAndCodeBlocks as $index => $preAndCodeBlock) {
            $html = str_replace(sprintf(self::CODE_BLOCK_PLACEHOLDER, $index), $preAndCodeBlock, $html);
        }
        return $html;
    }

    /**
     * Tabla = fila "| a | b |" seguida de una separadora "|---|:--:|" (que fija la alineacion).
     */
    private function getTableTags(string $html): string
    {
        $lines = explode("\n", $html);
        $tableLines = [];
        $i = 0;
        while ($i < count($lines)) {
            $line = trim($lines[$i]);
            $isTableStart = str_starts_with($line, "|")
                && isset($lines[$i + 1])
                && preg_match("/^\|[\s:|-]+\|$/", trim($lines[$i + 1]));
            if (!$isTableStart) {
                $tableLines[] = $lines[$i];
                $i++;
                continue;
            }

            $alignments = $this->getTableAlignments(trim($lines[$i + 1]));
            $tableHtml = "<table class=\"md-table\">\n<thead>\n<tr>"
                .$this->getTableCells($line, "th", $alignments)
                ."</tr>\n</thead>\n<tbody>";
            $i += 2;
            while ($i < count($lines) && str_starts_with(trim($lines[$i]), "|")) {
                $tableHtml .= "\n<tr>".$this->getTableCells(trim($lines[$i]), "td", $alignments)."</tr>";
                $i++;
            }
            $tableLines[] = $tableHtml."\n</tbody>\n</table>";
        }
        return implode("\n", $tableLines);
    }

    private function getTableCells(string $row, string $cellTag, array $alignments): string
    {
        $cellsHtml = "";
        foreach ($this->getTableRowCells($row) as $index => $cell) {
            $style = ($alignments[$index] ?? "") ? " style=\"text-align:{$alignments[$index]}\"" : "";
            $cellsHtml .= "<{$cellTag}{$style}>".trim($cell)."</{$cellTag}>";
        }
        return $cellsHtml;
    }

    private function getTableRowCells(string $row): array
    {
        return explode("|", trim(trim($row), "|"));
    }

    private function getTableAlignments(string $separatorRow): array
    {
        return array_map(
            function (string $cell): string {
                $cell = trim($cell);
                $isLeft = str_starts_with($cell, ":");
                $isRight = str_ends_with($cell, ":");
                return match (true) {
                    $isLeft && $isRight => "center",
                    $isRight => "right",
                    $isLeft => "left",
                    default => "",
                };
            },
            $this->getTableRowCells($separatorRow)
        );
    }

    private function getPTags(string $html): string
    {
        $paragraphs = array_filter(
            explode("\n\n", (string) preg_replace("/\n\n+/", "\n\n", $html)),
            fn(string $paragraph): bool => trim($paragraph) !== ""
        );

        return implode("\n", array_map(
            function (string $paragraph): string {
                $paragraph = trim($paragraph);
                foreach (self::BLOCK_TAG_PREFIXES as $blockTagPrefix) {
                    if (str_starts_with($paragraph, $blockTagPrefix)) return $paragraph;
                }
                if (str_contains($paragraph, "<table") || str_contains($paragraph, "<pre><code")) return $paragraph;

                return "<p>".str_replace("\n", "<br>", $paragraph)."</p>";
            },
            $paragraphs
        ));
    }
}
