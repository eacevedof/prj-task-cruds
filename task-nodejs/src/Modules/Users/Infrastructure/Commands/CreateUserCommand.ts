import { AbstractConsoleCommand } from "#app/Modules/Shared/Infrastructure/Commands/AbstractConsoleCommand.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";
import { FieldsException } from "#app/Modules/Shared/Infrastructure/Exceptions/FieldsException.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { CreateUserDto } from "#app/Modules/Users/Application/CreateUser/CreateUserDto.ts";
import { CreateUserService } from "#app/Modules/Users/Application/CreateUser/CreateUserService.ts";
import { UsersException } from "#app/Modules/Users/Domain/Exceptions/UsersException.ts";

/**
 * node cmd/console/main.ts user-create --user_name=ana.lopez --user_email=ana@example.com --password=<password>
 * Imprime la cuenta con su api_token en claro: es la unica vez que se ve.
 */
export class CreateUserCommand extends AbstractConsoleCommand {

    run(): void {
        try {
            const createUserResultDto = CreateUserService.getInstance().invoke(
                CreateUserDto.fromPrimitives({
                    userName: this.getOptionValue(UsersKeyEnum.USER_NAME),
                    userEmail: this.getOptionValue(UsersKeyEnum.USER_EMAIL),
                    password: this.getOptionValue(UsersKeyEnum.PASSWORD),
                })
            );
            this.printResult(createUserResultDto.toArray());
        }
        catch (e) {
            if (e instanceof FieldsException || e instanceof UsersException) {
                this.exitWithDomainError(e);
            }
            this.exitWithUnexpectedError(getThrowable(e), "CreateUserCommand::run");
        }
    }
}
