package modules.shared.infrastructure.components.logger;

import java.io.IOException;
import java.io.UncheckedIOException;
import java.nio.charset.StandardCharsets;
import java.nio.file.FileSystems;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.StandardOpenOption;
import java.nio.file.attribute.PosixFilePermissions;
import java.time.LocalDateTime;
import java.time.ZoneOffset;
import java.time.format.DateTimeFormatter;
import java.util.regex.Pattern;

import modules.shared.infrastructure.repositories.configuration.EnvironmentReaderRawRepository;

public final class Logger {

    private static final DateTimeFormatter FORMAT_DATE = DateTimeFormatter.ofPattern("yyyy-MM-dd");
    private static final DateTimeFormatter FORMAT_DATE_TIME = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss");
    private static final Pattern SQL_MARGIN_PATTERN = Pattern.compile("(?m)^ {8}");

    // los hilos de la API escriben en los mismos ficheros: una entrada no se mezcla con otra
    private static final Object WRITE_LOCK = new Object();

    public static Logger getInstance() {
        return new Logger();
    }

    public void writeLog(String filePath, String content) {
        writeLog(filePath, content, "");
    }

    public void writeLog(String filePath, String content, String logsPath) {
        logsPath = logsPath.isEmpty() ? EnvironmentReaderRawRepository.getInstance().getLogPath() : logsPath;
        if (logsPath.isEmpty()) return;

        try {
            Path pathLogs = Path.of(logsPath);
            if (!Files.exists(pathLogs)) {
                Files.createDirectories(pathLogs);
            }

            LocalDateTime now = LocalDateTime.now(ZoneOffset.UTC);
            Path logFilePath = pathLogs.toRealPath().resolve(filePath);
            Path finalDir = logFilePath.getParent();
            Files.createDirectories(finalDir);

            String baseName = logFilePath.getFileName().toString();
            int dotIndex = baseName.lastIndexOf('.');
            String name = dotIndex > 0 ? baseName.substring(0, dotIndex) : baseName;
            String ext = dotIndex > 0 ? baseName.substring(dotIndex + 1) : "log";
            if (filePath.contains("sql")) ext = "sql";

            Path finalLogFilePath = finalDir.resolve(now.format(FORMAT_DATE) + "-" + name + "." + ext);
            content = content.trim();
            if (ext.equals("sql")) content = getNormalizedMarginForSql(content);

            synchronized (WRITE_LOCK) {
                boolean isNewFile = !Files.exists(finalLogFilePath);
                Files.writeString(
                    finalLogFilePath,
                    "\n[" + now.format(FORMAT_DATE_TIME) + "]\n" + content,
                    StandardCharsets.UTF_8,
                    StandardOpenOption.CREATE,
                    StandardOpenOption.APPEND
                );
                if (isNewFile && FileSystems.getDefault().supportedFileAttributeViews().contains("posix")) {
                    Files.setPosixFilePermissions(finalLogFilePath, PosixFilePermissions.fromString("rw-rw-rw-"));
                }
            }
        }
        catch (IOException e) {
            throw new UncheckedIOException(e);
        }
    }

    private String getNormalizedMarginForSql(String content) {
        return SQL_MARGIN_PATTERN.matcher(content + "\n").replaceAll("");
    }
}
