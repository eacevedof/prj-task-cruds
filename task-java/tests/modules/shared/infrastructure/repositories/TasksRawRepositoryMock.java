package modules.shared.infrastructure.repositories;

/**
 * Tasks de los fixtures (FixturesRawRepositoryMock), todas de UsersRawRepositoryMock.USER_UUID_OK.
 */
public final class TasksRawRepositoryMock {

    public static final String TASK_UUID_OK = "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa";
    public static final String TASK_TITLE_OK = "Comprar pan";

    public static final String TASK_UUID_TO_UPDATE = "bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb";
    public static final String TASK_UUID_TO_DELETE = "cccccccc-cccc-4ccc-8ccc-cccccccccccc";
    public static final String TASK_UUID_TO_DELETE_GRAPHQL = "dddddddd-dddd-4ddd-8ddd-dddddddddddd";

    public static final String TASK_UUID_NOK = "ffffffff-ffff-4fff-8fff-ffffffffffff";

    // tasks de busqueda de UsersRawRepositoryMock.USER_UUID_SEARCH, insertadas en este orden (alpha id menor)
    public static final String SEARCH_TITLE_ALPHA = "Search alpha";
    public static final String SEARCH_TITLE_BETA = "Search beta";
    public static final String SEARCH_TITLE_GAMMA = "Search gamma";
    public static final String SEARCH_TARGET_DATE_ALPHA = "2026-03-10";
    public static final String SEARCH_TARGET_DATE_BETA = "2026-06-20";
    // con % literal: buscar "%" solo debe encontrar esta (el LIKE va escapado)
    public static final String SEARCH_DESCRIPTION_GAMMA = "100% done in the text";

    public static final String TARGET_DATE_OK = "2026-12-31";
    public static final String TARGET_DATE_INVALID = "2026-02-30";
    public static final String TASK_STATUS_INVALID = "archived";
    public static final String TASK_TYPE_INVALID = "hobby";

    private TasksRawRepositoryMock() {
    }
}
