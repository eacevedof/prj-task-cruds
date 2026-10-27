package modules.shared.infrastructure.exceptions;

import java.util.Arrays;
import java.util.stream.Collectors;

/**
 * Lo que tiene toda excepcion controlada (de dominio o FieldsException): su mensaje y un codigo HTTP 4xx. Es el
 * Exception de PHP que reciben respondDomainError() y exitWithDomainError().
 */
public interface InterfaceException {

    String getMessage();

    int getCode();

    /**
     * getFile() de un Throwable: el fichero (con la ruta de su paquete) del primer elemento de la traza.
     */
    static String getThrowableFile(Throwable throwable) {
        StackTraceElement[] stackTrace = throwable.getStackTrace();
        if (stackTrace.length == 0 || stackTrace[0].getFileName() == null) return "";

        String className = stackTrace[0].getClassName();
        int lastDot = className.lastIndexOf('.');
        String packagePath = lastDot < 0 ? "" : className.substring(0, lastDot).replace('.', '/') + "/";
        return packagePath + stackTrace[0].getFileName();
    }

    /**
     * getLine() de un Throwable: la linea del primer elemento de la traza.
     */
    static int getThrowableLine(Throwable throwable) {
        StackTraceElement[] stackTrace = throwable.getStackTrace();
        return stackTrace.length == 0 ? 0 : stackTrace[0].getLineNumber();
    }

    /**
     * getTraceAsString(): las lineas "at ..." de la traza, sin el mensaje.
     */
    static String getThrowableTraceAsString(Throwable throwable) {
        return Arrays.stream(throwable.getStackTrace())
            .map(stackTraceElement -> "at " + stackTraceElement)
            .collect(Collectors.joining("\n"));
    }
}
