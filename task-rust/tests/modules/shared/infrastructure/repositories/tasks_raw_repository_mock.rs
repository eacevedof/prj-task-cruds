/// tasks de los fixtures (FixturesRawRepositoryMock), todas de UsersRawRepositoryMock::USER_UUID_OK.
pub struct TasksRawRepositoryMock;

impl TasksRawRepositoryMock {
    pub const TASK_UUID_OK: &'static str = "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa";
    pub const TASK_TITLE_OK: &'static str = "Comprar pan";

    pub const TASK_UUID_TO_UPDATE: &'static str = "bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb";
    pub const TASK_UUID_TO_DELETE: &'static str = "cccccccc-cccc-4ccc-8ccc-cccccccccccc";
    pub const TASK_UUID_TO_DELETE_GRAPHQL: &'static str = "dddddddd-dddd-4ddd-8ddd-dddddddddddd";

    pub const TASK_UUID_NOK: &'static str = "ffffffff-ffff-4fff-8fff-ffffffffffff";

    // tasks de busqueda de UsersRawRepositoryMock::USER_UUID_SEARCH, insertadas en este orden (alpha id menor)
    pub const SEARCH_TITLE_ALPHA: &'static str = "Search alpha";
    pub const SEARCH_TITLE_BETA: &'static str = "Search beta";
    pub const SEARCH_TITLE_GAMMA: &'static str = "Search gamma";
    pub const SEARCH_TARGET_DATE_ALPHA: &'static str = "2026-03-10";
    pub const SEARCH_TARGET_DATE_BETA: &'static str = "2026-06-20";
    // con % literal: buscar "%" solo debe encontrar esta (el LIKE va escapado)
    pub const SEARCH_DESCRIPTION_GAMMA: &'static str = "100% done in the text";

    pub const TARGET_DATE_OK: &'static str = "2026-12-31";
    pub const TARGET_DATE_INVALID: &'static str = "2026-02-30";
    pub const TASK_STATUS_INVALID: &'static str = "archived";
    pub const TASK_TYPE_INVALID: &'static str = "hobby";
}
