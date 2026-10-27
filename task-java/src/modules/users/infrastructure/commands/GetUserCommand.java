package modules.users.infrastructure.commands;

import java.util.List;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.commands.AbstractConsoleCommand;
import modules.users.application.getuser.GetUserDto;
import modules.users.application.getuser.GetUserResultDto;
import modules.users.application.getuser.GetUserService;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.exceptions.UsersException;
import modules.users.infrastructure.traits.ConsoleAuthUserTrait;

/**
 * bin/console user-get --user_uuid=<uuid>
 * Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid (ConsoleAuthUserTrait).
 */
public final class GetUserCommand extends AbstractConsoleCommand {

    private final ConsoleAuthUserTrait consoleAuthUserTrait = ConsoleAuthUserTrait.getInstance();

    public GetUserCommand(List<String> input) {
        super(input);
    }

    @Override
    public void run() {
        try {
            String userUuid = getOptionValue(UsersKeyEnum.USER_UUID);
            GetUserResultDto getUserResultDto = GetUserService.getInstance().invoke(
                GetUserDto.fromPrimitives(Primitives.merge(
                    consoleAuthUserTrait.tryToGetConsoleAuthUserPrimitivesOrFail(userUuid),
                    Primitives.of("userUuid", userUuid)
                ))
            );
            printResult(getUserResultDto.toArray());
        }
        catch (UsersException ex) {
            exitWithDomainError(ex);
        }
        catch (Exception ex) {
            exitWithUnexpectedError(ex, "GetUserCommand::run");
        }
    }
}
