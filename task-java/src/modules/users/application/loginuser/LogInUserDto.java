package modules.users.application.loginuser;

import java.util.Map;

import modules.shared.application.AbstractInputDto;

import static modules.shared.application.Primitives.getTrimmedString;

public final class LogInUserDto extends AbstractInputDto {

    private final String userName;
    private final String password;

    private LogInUserDto(Map<String, Object> primitives) {
        super(primitives);

        this.userName = getTrimmedString(primitives.get("userName"));
        this.password = getTrimmedString(primitives.get("password"));
    }

    public static LogInUserDto fromPrimitives(Map<String, Object> primitives) {
        return new LogInUserDto(primitives);
    }

    public String getUserName() {
        return userName;
    }

    public String getPassword() {
        return password;
    }
}
