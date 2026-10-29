package modules.users.infrastructure.controllers.loginuserrestcontroller.mothers;

import modules.users.infrastructure.controllers.LogInUserRestController;

public final class LogInUserRestControllerMother {

    private LogInUserRestControllerMother() {
    }

    public static LogInUserRestController getControllerInstance() {
        return LogInUserRestController.getInstance();
    }
}
