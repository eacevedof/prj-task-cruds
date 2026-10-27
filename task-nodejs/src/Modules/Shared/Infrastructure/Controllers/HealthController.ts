import { HealthKeyEnum } from "#app/Modules/Shared/Domain/Enums/HealthKeyEnum.ts";
import type { HttpResponder } from "#app/Modules/Shared/Infrastructure/Components/HttpResponder/HttpResponder.ts";
import { AbstractApiController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractApiController.ts";

/**
 * GET /health
 */
export class HealthController extends AbstractApiController {

    static getInstance(): HealthController {
        return new HealthController();
    }

    index(): HttpResponder {
        return this.respondOk({
            [HealthKeyEnum.APP]: HealthKeyEnum.APP_NAME,
            [HealthKeyEnum.NODE]: process.version,
            [HealthKeyEnum.SQL_DRIVERS]: ["sqlite"],
        });
    }
}
