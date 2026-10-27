/**
 * El array asociativo de PHP: lo que recibe un dto (fromPrimitives) y lo que devuelven toPrimitives() y toArray().
 * Un objeto de JS conserva el orden de sus claves de texto, como el array de PHP.
 */
export type Primitives = Record<string, unknown>;

/**
 * trim((string) $value): null, false, un array o un objeto -> "", true -> "1", un numero -> su texto.
 */
export function getTrimmedString(value: unknown): string {
    if (typeof value === "string") return value.trim();
    if (typeof value === "number" || typeof value === "bigint") return String(value);
    if (value === true) return "1";
    return "";
}

/**
 * (int) $value: "12" -> 12, "12abc" -> 12, "abc" -> 0, 3.9 -> 3, true -> 1, null -> 0.
 */
export function getInt(value: unknown): number {
    if (typeof value === "number") return Number.isFinite(value) ? Math.trunc(value) : 0;
    if (typeof value === "bigint") return Number(value);
    if (typeof value === "boolean") return value ? 1 : 0;
    if (typeof value !== "string") return 0;

    const int = Number.parseInt(value.trim(), 10);
    return Number.isNaN(int) ? 0 : int;
}

/**
 * $primitives["key"] ?? []: el array anidado de un array de primitivos (un objeto JSON), o vacio.
 */
export function getPrimitives(value: unknown): Primitives {
    return value !== null && typeof value === "object" && !Array.isArray(value) ? value as Primitives : {};
}
