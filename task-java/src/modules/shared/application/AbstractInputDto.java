package modules.shared.application;

import java.util.Map;

import modules.shared.domain.enums.LanguageCodeEnum;

import static modules.shared.application.Primitives.getTrimmedString;

/**
 * Metadata de la peticion que viaja con TODO dto de entrada hasta la logica de negocio (ip, navegador, so,
 * zona horaria, idioma). La rellena el controller (HttpRequester.getMetadata), que es quien lee el transporte: el
 * servicio solo ve su dto. Desde CLI o un test llega vacia y el idioma cae en EN.
 * El dto hijo llama primero a super(primitives).
 */
public abstract class AbstractInputDto {

    private final String userIpAddress;
    private final String userBrowser;
    private final String userBrowserVersion;
    private final String userOs;
    private final String userTimeZone;
    private final String userLanguageCode;

    protected AbstractInputDto(Map<String, Object> primitives) {
        this.userIpAddress = getTrimmedString(primitives.get("userIpAddress"));
        this.userBrowser = getTrimmedString(primitives.get("userBrowser"));
        this.userBrowserVersion = getTrimmedString(primitives.get("userBrowserVersion"));
        this.userOs = getTrimmedString(primitives.get("userOs"));
        this.userTimeZone = getTrimmedString(primitives.get("userTimeZone"));
        String languageCode = getTrimmedString(primitives.get("userLanguageCode"));
        this.userLanguageCode = languageCode.isEmpty() ? LanguageCodeEnum.EN : languageCode;
    }

    public String getUserIpAddress() {
        return userIpAddress;
    }

    public String getUserBrowser() {
        return userBrowser;
    }

    public String getUserBrowserVersion() {
        return userBrowserVersion;
    }

    public String getUserOs() {
        return userOs;
    }

    public String getUserTimeZone() {
        return userTimeZone;
    }

    public String getUserLanguageCode() {
        return userLanguageCode;
    }
}
