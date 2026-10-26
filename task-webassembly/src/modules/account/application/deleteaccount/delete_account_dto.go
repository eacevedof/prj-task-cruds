package deleteaccount

import "task-webassembly/src/modules/shared/application"

// DeleteAccountDto entrada del caso de uso: solo primitivos, todo string con trim.
type DeleteAccountDto struct {
	apiToken string
	userUuid string
}

func DeleteAccountDtoFromPrimitives(primitives map[string]any) *DeleteAccountDto {
	return &DeleteAccountDto{
		apiToken: application.GetTrimmedString(primitives, "apiToken"),
		userUuid: application.GetTrimmedString(primitives, "userUuid"),
	}
}

func (d *DeleteAccountDto) GetApiToken() string {
	return d.apiToken
}

func (d *DeleteAccountDto) GetUserUuid() string {
	return d.userUuid
}
