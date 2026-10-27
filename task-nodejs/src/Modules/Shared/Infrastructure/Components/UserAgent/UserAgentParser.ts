/**
 * El ORDEN importa: Edge y Opera tambien dicen "Chrome", Chrome tambien dice "Safari".
 * nombre => patron cuyo grupo 1 es la version.
 */
const BROWSER_PATTERNS: Record<string, RegExp> = {
    Edge: /Edg(?:e|A|iOS)?\/([\d.]+)/,
    Opera: /(?:OPR|Opera)\/([\d.]+)/,
    Firefox: /(?:Firefox|FxiOS)\/([\d.]+)/,
    Chrome: /(?:Chrome|CriOS)\/([\d.]+)/,
    Safari: /Version\/([\d.]+).*Safari\//,
    Postman: /PostmanRuntime\/([\d.]+)/,
    curl: /^curl\/([\d.]+)/,
};

/**
 * El ORDEN importa: Android dice "Linux", iPhone/iPad dicen "Mac OS X".
 */
const OS_PATTERNS: Record<string, RegExp> = {
    Android: /Android/,
    iOS: /iPhone|iPad|iPod/,
    Windows: /Windows/,
    Mac: /Macintosh|Mac OS X/,
    Ubuntu: /Ubuntu/,
    Linux: /Linux/,
};

/**
 * Navegador, version y sistema operativo a partir del User-Agent. Deteccion por patrones (sin vendor):
 * suficiente para metadata/trazas, no para decidir permisos (el User-Agent lo escribe el cliente).
 */
export class UserAgentParser {

    static getInstance(): UserAgentParser {
        return new UserAgentParser();
    }

    getBrowserName(userAgent: string): string {
        for (const [browserName, pattern] of Object.entries(BROWSER_PATTERNS)) {
            if (pattern.test(userAgent)) return browserName;
        }
        return "";
    }

    getBrowserVersion(userAgent: string): string {
        for (const pattern of Object.values(BROWSER_PATTERNS)) {
            const matches = pattern.exec(userAgent);
            if (matches) return matches[1];
        }
        return "";
    }

    getOsName(userAgent: string): string {
        for (const [osName, pattern] of Object.entries(OS_PATTERNS)) {
            if (pattern.test(userAgent)) return osName;
        }
        return "";
    }
}
