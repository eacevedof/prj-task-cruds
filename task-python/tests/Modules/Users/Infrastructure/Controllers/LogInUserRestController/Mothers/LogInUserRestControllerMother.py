from Modules.Users.Infrastructure.Controllers.LogInUserRestController import LogInUserRestController


class LogInUserRestControllerMother:

    @staticmethod
    def getControllerInstance() -> LogInUserRestController:
        return LogInUserRestController()
