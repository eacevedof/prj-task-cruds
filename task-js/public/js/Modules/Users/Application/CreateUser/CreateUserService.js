import { UsersKeyEnum } from "/js/Modules/Users/Domain/Enums/UsersKeyEnum.js";
import { UsersMessageEnum } from "/js/Modules/Users/Domain/Enums/UsersMessageEnum.js";
import { CreateUserResultDto } from "/js/Modules/Users/Application/CreateUser/CreateUserResultDto.js";
import { UsersWriterGraphqlRepository } from "/js/Modules/Users/Infrastructure/Repositories/UsersWriterGraphqlRepository.js";
import { UsersException } from "/js/Modules/Users/Domain/Exceptions/UsersException.js";

/**
 * Alta abierta: la API devuelve el usuario con su api_token. Aqui solo se exige que lleguen los campos: el
 * resto de reglas (formato de user_name, longitud de la password, user_name y email unicos) las aplica ella.
 */
export class CreateUserService {

    #usersWriterGraphqlRepository;

    constructor() {
        this.#usersWriterGraphqlRepository = UsersWriterGraphqlRepository.getInstance();
    }

    /**
     * @returns {CreateUserService}
     */
    static getInstance() {
        return new CreateUserService();
    }

    /**
     * @param {import("./CreateUserDto.js").CreateUserDto} createUserDto
     * @returns {Promise<CreateUserResultDto>}
     * @throws {UsersException}
     */
    async invoke(createUserDto) {
        const errors = [];
        if (!createUserDto.getUserName()) errors.push(UsersMessageEnum.USER_NAME_IS_REQUIRED);
        if (!createUserDto.getUserEmail()) errors.push(UsersMessageEnum.USER_EMAIL_IS_REQUIRED);
        if (!createUserDto.getPassword()) errors.push(UsersMessageEnum.PASSWORD_IS_REQUIRED);
        if (errors.length) {
            UsersException.badRequestCustom(errors);
        }

        const apiResponse = await this.#usersWriterGraphqlRepository.createUser(
            createUserDto.getUserName(),
            createUserDto.getUserEmail(),
            createUserDto.getPassword()
        );
        if (!apiResponse.status) {
            UsersException.fromApiResponse(apiResponse);
        }

        return CreateUserResultDto.fromPrimitives({
            apiToken: apiResponse.data[UsersKeyEnum.API_TOKEN],
            userUuid: apiResponse.data[UsersKeyEnum.USER_UUID],
            userName: apiResponse.data[UsersKeyEnum.USER_NAME],
            userEmail: apiResponse.data[UsersKeyEnum.USER_EMAIL],
        });
    }
}
