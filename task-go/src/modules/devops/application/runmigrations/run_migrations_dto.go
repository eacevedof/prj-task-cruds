package runmigrations

import "task-go/src/modules/shared/application"

type RunMigrationsDto struct {
	application.AbstractInputDto
}

func RunMigrationsDtoFromPrimitives(primitives map[string]any) *RunMigrationsDto {
	return &RunMigrationsDto{AbstractInputDto: application.NewAbstractInputDto(primitives)}
}
