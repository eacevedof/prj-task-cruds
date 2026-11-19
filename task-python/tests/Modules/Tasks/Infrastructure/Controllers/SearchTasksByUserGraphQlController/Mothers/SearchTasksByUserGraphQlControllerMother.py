from Modules.Tasks.Infrastructure.Controllers.SearchTasksByUserGraphQlController import SearchTasksByUserGraphQlController


class SearchTasksByUserGraphQlControllerMother:

    @staticmethod
    def getControllerInstance() -> SearchTasksByUserGraphQlController:
        return SearchTasksByUserGraphQlController.getInstance()
