<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Components\Formatter;

use InvalidArgumentException;
use ReflectionObject;

final class TextComponent
{
    public static function getInstance(): self
    {
        return new self();
    }

    public function getSlug(string $toSlug): string
    {
        $divider = "-";
        $toSlug = trim($toSlug);
        $toSlug = preg_replace("/\s+/", " ", $toSlug);
        $toSlug = str_replace(" ", $divider, $toSlug);
        $toSlug = $this->getWithoutAccents($toSlug);
        $text = preg_replace("~[^\pL\d]+~u", $divider, $toSlug);
        // transliterate
        $text = iconv("utf-8", "us-ascii//TRANSLIT", $text);
        // remove unwanted characters
        $text = preg_replace("~[^-\w]+~", "", $text);
        $text = trim($text, $divider);
        // remove duplicate divider
        $text = preg_replace("~-+~", $divider, $text);
        // lowercase
        $text = strtolower($text);
        return $text;
    }

    public function getRandomWord(int $charLen = 4, int $numbers = 2): string
    {
        $chars = "bcdfghjklmnpqrstvxyz";
        $chars = str_split($chars);
        $vocals = "aeiou";
        $vocals = str_split($vocals);

        $word = [];
        for($i = 0; $i < $charLen; $i++) {
            $word[] = (($i % 2) === 0) ? $chars[array_rand($chars)] : $vocals[array_rand($vocals)];
        }

        if (!$numbers) {
            return strtoupper(implode("", $word));
        }
        for($i = 0; $i < $numbers; $i++) {
            $word[] = rand(0, 9);
        }
        return strtoupper(implode("", $word));
    }

    public function getBlanksAsNull(string $csv): string
    {
        $csv = trim(strtolower($csv));
        if (!strstr($csv, ",")) {
            return $csv;
        }

        $parts = explode(",", $csv);
        $parts = array_map(function (string $string) {
            $string = trim($string);
            return !$string ? null : $string;
        }, $parts);

        $parts = array_filter($parts);
        $parts = array_unique($parts);
        return implode(", ", $parts);
    }

    public function getCancelledEmail(string $email): string
    {
        $email = explode("@", $email)[0];
        return "$email@cancelled.can";
    }

    /**
     * Convierte un DTO en array de request params (snake_case) leyendo sus accessors.
     * Acepta el convenio actual getFoo() y el heredado foo(); si una propiedad no expone
     * ninguno de los dos falla explicito, en vez de morir con "Call to undefined method".
     *
     * Preferir getRequestParams() en el propio DTO cuando el mapeo no sea 1:1 con las
     * propiedades (p.ej. UpdateUsersDto), esto es solo el fallback generico.
     */
    public function getObjectAsArrayInSnakeCase(object $objectDto): array
    {
        $asArray = [];
        $reflection = new ReflectionObject($objectDto);
        $reflectionProperties = $reflection->getProperties();
        $reflectionProperties = array_map(fn ($item) => $item->getName(), $reflectionProperties);
        foreach ($reflectionProperties as $reflectionProperty) {
            $accessor = "get" . ucfirst($reflectionProperty);
            if (!method_exists($objectDto, $accessor)) {
                $accessor = $reflectionProperty;
            }
            if (!method_exists($objectDto, $accessor)) {
                throw new InvalidArgumentException(sprintf(
                    "%s::\$%s has no accessor (get%s() nor %s())",
                    $objectDto::class,
                    $reflectionProperty,
                    ucfirst($reflectionProperty),
                    $reflectionProperty
                ));
            }
            $value = $objectDto->{$accessor}();
            $propertySnake = preg_replace("/([a-z])([A-Z])/", "$1_$2", $reflectionProperty);
            $propertySnake = strtolower($propertySnake);
            $asArray[$propertySnake] = $value;
        }
        return $asArray;
    }

    private function getWithoutAccents(string $withAccents): string
    {
        //Reemplazamos la A y a
        $withAccents = str_replace(
            ["Á", "À", "Â", "Ä", "á", "à", "ä", "â", "ª"],
            ["A", "A", "A", "A", "a", "a", "a", "a", "a"],
            $withAccents
        );

        //Reemplazamos la E y e
        $withAccents = str_replace(
            ["É", "È", "Ê", "Ë", "é", "è", "ë", "ê"],
            ["E", "E", "E", "E", "e", "e", "e", "e"],
            $withAccents
        );

        //Reemplazamos la I y i
        $withAccents = str_replace(
            ["Í", "Ì", "Ï", "Î", "í", "ì", "ï", "î"],
            ["I", "I", "I", "I", "i", "i", "i", "i"],
            $withAccents
        );

        //Reemplazamos la O y o
        $withAccents = str_replace(
            ["Ó", "Ò", "Ö", "Ô", "ó", "ò", "ö", "ô"],
            ["O", "O", "O", "O", "o", "o", "o", "o"],
            $withAccents
        );

        //Reemplazamos la U y u
        $withAccents = str_replace(
            ["Ú", "Ù", "Û", "Ü", "ú", "ù", "ü", "û"],
            ["U", "U", "U", "U", "u", "u", "u", "u"],
            $withAccents
        );

        //Reemplazamos la N, n, C y c
        $withAccents = str_replace(
            ["Ñ", "ñ", "Ç", "ç"],
            ["N", "n", "C", "c"],
            $withAccents
        );

        return $withAccents;
    }
}
