import { LogInUserRestController } from "#app/Modules/Users/Infrastructure/Controllers/LogInUserRestController.ts";

export class LogInUserRestControllerMother {

    static getControllerInstance(): LogInUserRestController {
        return new LogInUserRestController();
    }
}
