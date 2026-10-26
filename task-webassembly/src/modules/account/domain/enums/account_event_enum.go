package enums

var AccountEventEnum = struct {
	UPDATE_ACCOUNT_REQUESTED       string
	REGENERATE_API_TOKEN_REQUESTED string
	DELETE_ACCOUNT_REQUESTED       string
}{
	UPDATE_ACCOUNT_REQUESTED:       "account.update-account-requested",
	REGENERATE_API_TOKEN_REQUESTED: "account.regenerate-api-token-requested",
	DELETE_ACCOUNT_REQUESTED:       "account.delete-account-requested",
}
