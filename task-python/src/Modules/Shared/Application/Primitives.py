"""
El array asociativo de PHP: lo que recibe un dto (fromPrimitives) y lo que devuelven toPrimitives() y toArray().
Un dict de Python conserva el orden de insercion de sus claves, como el array de PHP.
"""
import math
import re

Primitives = dict


def getTrimmedString(value) -> str:
    """
    trim((string) $value): None, False, una lista o un dict -> "", True -> "1", un numero -> su texto.
    """
    if isinstance(value, str):
        return value.strip()
    if isinstance(value, bool):
        return "1" if value else ""
    if isinstance(value, int):
        return str(value)
    if isinstance(value, float):
        return str(int(value)) if value.is_integer() else str(value)
    return ""


def getInt(value) -> int:
    """
    (int) $value: "12" -> 12, "12abc" -> 12, "abc" -> 0, 3.9 -> 3, True -> 1, None -> 0.
    """
    if isinstance(value, bool):
        return 1 if value else 0
    if isinstance(value, int):
        return value
    if isinstance(value, float):
        return math.trunc(value) if math.isfinite(value) else 0
    if not isinstance(value, str):
        return 0

    matches = re.match(r"^[+-]?\d+", value.strip())
    return int(matches.group(0)) if matches else 0


def getPrimitives(value) -> dict:
    """
    $primitives["key"] ?? []: el array anidado de un array de primitivos (un objeto JSON), o vacio.
    """
    return value if isinstance(value, dict) else {}
