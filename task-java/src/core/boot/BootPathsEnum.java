package core.boot;

import java.nio.file.Path;

/**
 * Rutas de la app. PATH_ROOT es la carpeta del proyecto: la de las clases compiladas (target/classes) dos niveles
 * arriba. Con "/" tambien en Windows: es como se escriben las rutas en .env
 */
public final class BootPathsEnum {

    public static final String PATH_ROOT = getPathRoot();
    public static final String PATH_PUBLIC = PATH_ROOT + "/public";
    public static final String PATH_SRC = PATH_ROOT + "/src";
    public static final String PATH_DISK_CACHE = PATH_ROOT + "/storage/cache";
    public static final String PATH_SRC_CONFIG = PATH_ROOT + "/src/core/config";
    public static final String PATH_LOGS = PATH_ROOT + "/storage/logs";
    public static final String PATH_CONSOLE = PATH_ROOT + "/src/console";
    public static final String PATH_MIGRATIONS = PATH_ROOT + "/database/migrations";

    private BootPathsEnum() {
    }

    /**
     * target/classes -> target -> <project root>. Si no se puede saber (clases fuera de un directorio), el directorio
     * de trabajo.
     */
    private static String getPathRoot() {
        try {
            Path pathClasses = Path.of(BootPathsEnum.class.getProtectionDomain().getCodeSource().getLocation().toURI());
            return pathClasses.toAbsolutePath().getParent().getParent().toString().replace("\\", "/");
        }
        catch (Exception e) {
            return Path.of("").toAbsolutePath().toString().replace("\\", "/");
        }
    }
}
