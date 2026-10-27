import { LogInUserController } from "#app/Modules/Users/Infrastructure/Controllers/LogInUserController.ts";

export class LogInUserControllerMother {

    static getControllerInstance(): LogInUserController {
        return new LogInUserController();
    }
}
