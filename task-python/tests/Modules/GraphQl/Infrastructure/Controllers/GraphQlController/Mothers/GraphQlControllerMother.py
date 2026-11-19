from Modules.GraphQl.Infrastructure.Controllers.ExecuteGraphQlQueryController import ExecuteGraphQlQueryController


class GraphQlControllerMother:

    @staticmethod
    def getControllerInstance() -> ExecuteGraphQlQueryController:
        return ExecuteGraphQlQueryController()
