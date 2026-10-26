package regenerateuserapitoken

import "task-go/src/modules/shared/application"

// RegenerateUserApiTokenResultPrimitives tipo ApiTokenResult de schema.graphql.
type RegenerateUserApiTokenResultPrimitives struct {
	UserUuid string `json:"user_uuid"`
	ApiToken string `json:"api_token"`
}

type RegenerateUserApiTokenResultDto struct {
	userUuid string
	apiToken string
}

func RegenerateUserApiTokenResultDtoFromPrimitives(primitives map[string]any) *RegenerateUserApiTokenResultDto {
	return &RegenerateUserApiTokenResultDto{
		userUuid: application.GetTrimmedString(primitives, "userUuid"),
		apiToken: application.GetTrimmedString(primitives, "apiToken"),
	}
}

func (d *RegenerateUserApiTokenResultDto) GetUserUuid() string {
	return d.userUuid
}

func (d *RegenerateUserApiTokenResultDto) GetApiToken() string {
	return d.apiToken
}

func (d *RegenerateUserApiTokenResultDto) ToArray() RegenerateUserApiTokenResultPrimitives {
	return RegenerateUserApiTokenResultPrimitives{UserUuid: d.userUuid, ApiToken: d.apiToken}
}
