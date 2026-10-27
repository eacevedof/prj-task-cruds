package modules.users.infrastructure.commands;

import java.util.List;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.commands.AbstractConsoleCommand;
import modules.users.application.regenerateuserapitoken.RegenerateUserApiTokenDto;
import modules.users.application.regenerateuserapitoken.RegenerateUserApiTokenResultDto;
import modules.users.application.regenerateuserapitoken.RegenerateUserApiTokenService;
import modules.users.domain.enums.UsersKeyEnum;
import modules.users.domain.exceptions.UsersException;
import modules.users.infrastructure.traits.ConsoleAuthUserTrait;

/**
 * bin/console user-regenerate-api-token --user_uuid=<uuid>
 * El token anterior deja de valer; el nuevo se imprime en claro solo aqui.
 * Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid (ConsoleAuthUserTrait).
 */
public final class RegenerateUserApiTokenCommand extends AbstractConsoleCommand {

    private final ConsoleAuthUserTrait consoleAuthUserTrait = ConsoleAuthUserTrait.getInstance();

    public RegenerateUserApiTokenCommand(List<String> input) {
        super(input);
    }

    @Override
    public void run() {
        try {
            String userUuid = getOptionValue(UsersKeyEnum.USER_UUID);
            RegenerateUserApiTokenResultDto regenerateUserApiTokenResultDto = RegenerateUserApiTokenService.getInstance().invoke(
                RegenerateUserApiTokenDto.fromPrimitives(Primitives.merge(
                    consoleAuthUserTrait.tryToGetConsoleAuthUserPrimitivesOrFail(userUuid),
                    Primitives.of("userUuid", userUuid)
                ))
            );
            printResult(regenerateUserApiTokenResultDto.toArray());
        }
        catch (UsersException ex) {
            exitWithDomainError(ex);
        }
        catch (Exception ex) {
            exitWithUnexpectedError(ex, "RegenerateUserApiTokenCommand::run");
        }
    }
}
