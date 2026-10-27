package modules.open.documentation.infrastructure.views;

import java.util.ArrayList;
import java.util.List;
import java.util.Map;

import static modules.shared.application.Primitives.getTrimmedString;

public final class DocumentationViewDto {

    private final String appVersion;
    private final String appVersionUpdate;
    private final String appBaseUrl;
    private final List<String> errors;

    private DocumentationViewDto(String appVersion, String appVersionUpdate, String appBaseUrl, List<String> errors) {
        this.appVersion = appVersion;
        this.appVersionUpdate = appVersionUpdate;
        this.appBaseUrl = appBaseUrl;
        this.errors = errors;
    }

    public static DocumentationViewDto fromPrimitives(Map<String, Object> primitives) {
        List<String> errors = new ArrayList<>();
        if (primitives.get("errors") instanceof List<?> rawErrors) {
            for (Object error : rawErrors) {
                errors.add(getTrimmedString(error));
            }
        }
        return new DocumentationViewDto(
            getTrimmedString(primitives.get("appVersion")),
            getTrimmedString(primitives.get("appVersionUpdate")),
            getTrimmedString(primitives.get("appBaseUrl")),
            errors
        );
    }

    public String getAppVersion() {
        return appVersion;
    }

    public String getAppVersionUpdate() {
        return appVersionUpdate;
    }

    public String getAppBaseUrl() {
        return appBaseUrl;
    }

    public List<String> getErrors() {
        return errors;
    }
}
