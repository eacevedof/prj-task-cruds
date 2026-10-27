package modules.users.application.createuser;

import java.util.Map;

import modules.shared.application.AbstractInputDto;

import static modules.shared.application.Primitives.getTrimmedString;

public final class CreateUserDto extends AbstractInputDto {

    private final String userName;
    private final String userEmail;
    private final String password;

    private CreateUserDto(Map<String, Object> primitives) {
        super(primitives);

        this.userName = getTrimmedString(primitives.get("userName"));
        this.userEmail = getTrimmedString(primitives.get("userEmail"));
        this.password = getTrimmedString(primitives.get("password"));
    }

    public static CreateUserDto fromPrimitives(Map<String, Object> primitives) {
        return new CreateUserDto(primitives);
    }

    public String getUserName() {
        return userName;
    }

    public String getUserEmail() {
        return userEmail;
    }

    public String getPassword() {
        return password;
    }
}
