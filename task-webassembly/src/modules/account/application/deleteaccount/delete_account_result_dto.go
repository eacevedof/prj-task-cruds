package deleteaccount

import "task-webassembly/src/modules/shared/application"

// DeleteAccountResultDto resultado del caso de uso: solo primitivos.
type DeleteAccountResultDto struct {
	userUuid string
}

func DeleteAccountResultDtoFromPrimitives(primitives map[string]any) *DeleteAccountResultDto {
	return &DeleteAccountResultDto{
		userUuid: application.GetTrimmedString(primitives, "userUuid"),
	}
}

func (d *DeleteAccountResultDto) GetUserUuid() string {
	return d.userUuid
}
