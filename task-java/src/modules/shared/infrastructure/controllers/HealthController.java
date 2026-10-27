package modules.shared.infrastructure.controllers;

import java.util.List;

import modules.shared.application.Primitives;
import modules.shared.domain.enums.HealthKeyEnum;
import modules.shared.infrastructure.components.httpresponder.HttpResponder;

/**
 * GET /health
 */
public final class HealthController extends AbstractApiController {

    public static HealthController getInstance() {
        return new HealthController();
    }

    public HttpResponder index() {
        return respondOk(Primitives.of(
            HealthKeyEnum.APP, HealthKeyEnum.APP_NAME,
            HealthKeyEnum.JAVA, System.getProperty("java.version"),
            HealthKeyEnum.SQL_DRIVERS, List.of("sqlite")
        ));
    }
}
