from pathlib import Path

# src/Core/Boot/BootPathsEnum.py -> Boot -> Core -> src -> <project root>. Con "/" tambien en Windows: es como se
# escriben las rutas en .env
_PATH_ROOT = str(Path(__file__).resolve().parents[3]).replace("\\", "/").rstrip("/")


class BootPathsEnum:
    PATH_ROOT = _PATH_ROOT
    PATH_PUBLIC = f"{_PATH_ROOT}/public"
    PATH_SRC = f"{_PATH_ROOT}/src"
    PATH_DISK_CACHE = f"{_PATH_ROOT}/storage/cache"
    PATH_SRC_CONFIG = f"{_PATH_ROOT}/src/Core/config"
    PATH_LOGS = f"{_PATH_ROOT}/storage/logs"
    PATH_CONSOLE = f"{_PATH_ROOT}/console"
    PATH_MIGRATIONS = f"{_PATH_ROOT}/database/migrations"
