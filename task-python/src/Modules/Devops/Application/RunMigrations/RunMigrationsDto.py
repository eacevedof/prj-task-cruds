from Modules.Shared.Application.AbstractInputDto import AbstractInputDto
from Modules.Shared.Application.Primitives import Primitives


class RunMigrationsDto(AbstractInputDto):

    @staticmethod
    def fromPrimitives(primitives: Primitives) -> "RunMigrationsDto":
        return RunMigrationsDto(primitives)
