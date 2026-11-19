import re

# El ORDEN importa: Edge y Opera tambien dicen "Chrome", Chrome tambien dice "Safari".
# nombre => patron cuyo grupo 1 es la version.
_BROWSER_PATTERNS = {
    "Edge": re.compile(r"Edg(?:e|A|iOS)?/([\d.]+)"),
    "Opera": re.compile(r"(?:OPR|Opera)/([\d.]+)"),
    "Firefox": re.compile(r"(?:Firefox|FxiOS)/([\d.]+)"),
    "Chrome": re.compile(r"(?:Chrome|CriOS)/([\d.]+)"),
    "Safari": re.compile(r"Version/([\d.]+).*Safari/"),
    "Postman": re.compile(r"PostmanRuntime/([\d.]+)"),
    "curl": re.compile(r"^curl/([\d.]+)"),
}

# El ORDEN importa: Android dice "Linux", iPhone/iPad dicen "Mac OS X".
_OS_PATTERNS = {
    "Android": re.compile(r"Android"),
    "iOS": re.compile(r"iPhone|iPad|iPod"),
    "Windows": re.compile(r"Windows"),
    "Mac": re.compile(r"Macintosh|Mac OS X"),
    "Ubuntu": re.compile(r"Ubuntu"),
    "Linux": re.compile(r"Linux"),
}


class UserAgentParser:
    """
    Navegador, version y sistema operativo a partir del User-Agent. Deteccion por patrones (sin vendor):
    suficiente para metadata/trazas, no para decidir permisos (el User-Agent lo escribe el cliente).
    """

    @staticmethod
    def getInstance() -> "UserAgentParser":
        return UserAgentParser()

    def getBrowserName(self, userAgent: str) -> str:
        for browserName, pattern in _BROWSER_PATTERNS.items():
            if pattern.search(userAgent):
                return browserName
        return ""

    def getBrowserVersion(self, userAgent: str) -> str:
        for pattern in _BROWSER_PATTERNS.values():
            matches = pattern.search(userAgent)
            if matches:
                return matches.group(1)
        return ""

    def getOsName(self, userAgent: str) -> str:
        for osName, pattern in _OS_PATTERNS.items():
            if pattern.search(userAgent):
                return osName
        return ""
