package controllers

import (
	"database/sql"
	"runtime"

	"task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/shared/infrastructure/components/httpresponder"
)

// HealthController GET /health
type HealthController struct {
	AbstractApiController
}

func HealthControllerGetInstance(httpRequester *httprequester.HttpRequester) *HealthController {
	return &HealthController{AbstractApiController: NewAbstractApiController(httpRequester)}
}

func (c *HealthController) Index() *httpresponder.HttpResponder {
	return c.RespondOk(map[string]any{
		enums.HealthKeyEnum.APP:         enums.HealthKeyEnum.APP_NAME,
		enums.HealthKeyEnum.GO:          runtime.Version(),
		enums.HealthKeyEnum.SQL_DRIVERS: sql.Drivers(),
	})
}
