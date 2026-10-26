package repositories

// TasksRawRepositoryMock tasks de los fixtures (FixturesRawRepositoryMock), todas de UsersRawRepositoryMock.USER_UUID_OK.
var TasksRawRepositoryMock = struct {
	TASK_UUID_OK  string
	TASK_TITLE_OK string

	TASK_UUID_TO_UPDATE         string
	TASK_UUID_TO_DELETE         string
	TASK_UUID_TO_DELETE_GRAPHQL string

	TASK_UUID_NOK string

	SEARCH_TITLE_ALPHA       string
	SEARCH_TITLE_BETA        string
	SEARCH_TITLE_GAMMA       string
	SEARCH_TARGET_DATE_ALPHA string
	SEARCH_TARGET_DATE_BETA  string
	SEARCH_DESCRIPTION_GAMMA string

	TARGET_DATE_OK      string
	TARGET_DATE_INVALID string
	TASK_STATUS_INVALID string
	TASK_TYPE_INVALID   string
}{
	TASK_UUID_OK:  "aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa",
	TASK_TITLE_OK: "Comprar pan",

	TASK_UUID_TO_UPDATE:         "bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb",
	TASK_UUID_TO_DELETE:         "cccccccc-cccc-4ccc-8ccc-cccccccccccc",
	TASK_UUID_TO_DELETE_GRAPHQL: "dddddddd-dddd-4ddd-8ddd-dddddddddddd",

	TASK_UUID_NOK: "ffffffff-ffff-4fff-8fff-ffffffffffff",

	// tasks de busqueda de UsersRawRepositoryMock.USER_UUID_SEARCH, insertadas en este orden (alpha id menor)
	SEARCH_TITLE_ALPHA:       "Search alpha",
	SEARCH_TITLE_BETA:        "Search beta",
	SEARCH_TITLE_GAMMA:       "Search gamma",
	SEARCH_TARGET_DATE_ALPHA: "2026-03-10",
	SEARCH_TARGET_DATE_BETA:  "2026-06-20",
	// con % literal: buscar "%" solo debe encontrar esta (el LIKE va escapado)
	SEARCH_DESCRIPTION_GAMMA: "100% done in the text",

	TARGET_DATE_OK:      "2026-12-31",
	TARGET_DATE_INVALID: "2026-02-30",
	TASK_STATUS_INVALID: "archived",
	TASK_TYPE_INVALID:   "hobby",
}
