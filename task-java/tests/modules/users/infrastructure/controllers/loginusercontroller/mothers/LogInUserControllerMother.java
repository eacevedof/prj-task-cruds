package modules.users.infrastructure.controllers.loginusercontroller.mothers;

import modules.users.infrastructure.controllers.LogInUserController;

public final class LogInUserControllerMother {

    private LogInUserControllerMother() {
    }

    public static LogInUserController getControllerInstance() {
        return LogInUserController.getInstance();
    }
}
