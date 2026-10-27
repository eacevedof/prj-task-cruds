package modules.shared.infrastructure.components.http.httprequest.mothers;

import java.util.List;

public final class RequestMetadataTestCaseEnum {

    public static final String OK_AS_CHROME_ON_WINDOWS_IN_SPANISH = "OK_AS_CHROME_ON_WINDOWS_IN_SPANISH";
    public static final String OK_AS_SAFARI_ON_IPHONE_IN_ENGLISH = "OK_AS_SAFARI_ON_IPHONE_IN_ENGLISH";
    public static final String OK_AS_EDGE_NOT_CHROME_ON_WINDOWS = "OK_AS_EDGE_NOT_CHROME_ON_WINDOWS";
    public static final String OK_AS_ANDROID_NOT_LINUX = "OK_AS_ANDROID_NOT_LINUX";
    public static final String OK_AS_DEFAULTS_BY_CURL_WITHOUT_HEADERS = "OK_AS_DEFAULTS_BY_CURL_WITHOUT_HEADERS";
    public static final String OK_AS_DEFAULTS_BY_UNSUPPORTED_LANGUAGE_AND_INVALID_TIME_ZONE = "OK_AS_DEFAULTS_BY_UNSUPPORTED_LANGUAGE_AND_INVALID_TIME_ZONE";

    public static final List<String> VALUES = List.of(
        OK_AS_CHROME_ON_WINDOWS_IN_SPANISH,
        OK_AS_SAFARI_ON_IPHONE_IN_ENGLISH,
        OK_AS_EDGE_NOT_CHROME_ON_WINDOWS,
        OK_AS_ANDROID_NOT_LINUX,
        OK_AS_DEFAULTS_BY_CURL_WITHOUT_HEADERS,
        OK_AS_DEFAULTS_BY_UNSUPPORTED_LANGUAGE_AND_INVALID_TIME_ZONE
    );

    private RequestMetadataTestCaseEnum() {
    }
}
