/// usuarios de los fixtures (FixturesRawRepositoryMock). Uno por escritura para que el orden de los tests no importe:
/// OK solo se lee, TO_UPDATE se modifica, TO_DELETE se borra y TO_REGENERATE cambia de token. Cada uno con su
/// api_token (en BD solo va su hash, como en produccion). Solo TO_LOG_IN tiene password: el login le rota el token,
/// por eso no lo usa ningun otro test.
pub struct UsersRawRepositoryMock;

impl UsersRawRepositoryMock {
    pub const USER_UUID_OK: &'static str = "11111111-1111-4111-8111-111111111111";
    pub const USER_NAME_OK: &'static str = "ana.test";
    pub const USER_EMAIL_OK: &'static str = "ana@task-cruds.test";
    pub const USER_API_TOKEN_OK: &'static str = "token-test-ana-0000000000000000000000000000000000000000000000000";

    pub const USER_UUID_TO_UPDATE: &'static str = "22222222-2222-4222-8222-222222222222";
    pub const USER_EMAIL_TO_UPDATE: &'static str = "luis@task-cruds.test";
    pub const USER_API_TOKEN_TO_UPDATE: &'static str =
        "token-test-luis-000000000000000000000000000000000000000000000000";

    pub const USER_UUID_TO_DELETE: &'static str = "33333333-3333-4333-8333-333333333333";
    pub const USER_EMAIL_TO_DELETE: &'static str = "eva@task-cruds.test";
    pub const USER_API_TOKEN_TO_DELETE: &'static str =
        "token-test-eva-0000000000000000000000000000000000000000000000000";

    pub const USER_UUID_TO_REGENERATE: &'static str = "44444444-4444-4444-8444-444444444444";
    pub const USER_EMAIL_TO_REGENERATE: &'static str = "rosa@task-cruds.test";
    pub const USER_API_TOKEN_TO_REGENERATE: &'static str =
        "token-test-rosa-000000000000000000000000000000000000000000000000";

    // dueño de las tasks de busqueda (TasksRawRepositoryMock::SEARCH_*): nadie mas las toca, los totales son fijos
    pub const USER_UUID_SEARCH: &'static str = "55555555-5555-4555-8555-555555555555";
    pub const USER_EMAIL_SEARCH: &'static str = "sara@task-cruds.test";
    pub const USER_API_TOKEN_SEARCH: &'static str = "token-test-sara-000000000000000000000000000000000000000000000000";

    pub const USER_UUID_TO_LOG_IN: &'static str = "66666666-6666-4666-8666-666666666666";
    pub const USER_NAME_TO_LOG_IN: &'static str = "tina.test";
    pub const USER_EMAIL_TO_LOG_IN: &'static str = "tina@task-cruds.test";
    pub const USER_PASSWORD_TO_LOG_IN: &'static str = "Tina.Secret1";
    pub const USER_API_TOKEN_TO_LOG_IN: &'static str =
        "token-test-tina-000000000000000000000000000000000000000000000000";

    pub const USER_UUID_NOK: &'static str = "99999999-9999-4999-8999-999999999999";
    pub const USER_NAME_NOK: &'static str = "nadie.test";
    pub const USER_PASSWORD_NOK: &'static str = "Wrong.Secret1";
    pub const USER_API_TOKEN_NOK: &'static str = "token-que-no-existe";

    pub const USER_NAME_NEW: &'static str = "nuevo.test";
    pub const USER_NAME_NEW_GRAPHQL: &'static str = "nuevo.graphql";
    pub const USER_NAME_NEW_GRAPHQL_WITHOUT_TOKEN: &'static str = "sin.token.graphql";
    pub const USER_NAME_OTHER: &'static str = "otro.test";
    pub const USER_NAME_UPDATED: &'static str = "luis.updated";
    pub const USER_NAME_INVALID: &'static str = "con espacios";
    pub const USER_PASSWORD_NEW: &'static str = "Nuevo.Secret1";

    pub const USER_EMAIL_NEW: &'static str = "nuevo@task-cruds.test";
    pub const USER_EMAIL_NEW_GRAPHQL: &'static str = "nuevo.graphql@task-cruds.test";
    pub const USER_EMAIL_NEW_GRAPHQL_WITHOUT_TOKEN: &'static str = "sin.token.graphql@task-cruds.test";
    pub const USER_EMAIL_UPDATED: &'static str = "luis.updated@task-cruds.test";
    pub const USER_EMAIL_INVALID: &'static str = "no-es-un-email";
}
