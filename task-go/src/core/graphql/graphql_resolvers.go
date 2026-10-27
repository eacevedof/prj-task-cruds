package graphql

import (
	"context"

	graphqlgo "github.com/graph-gophers/graphql-go"

	"task-go/src/modules/tasks/application/deletetask"
	"task-go/src/modules/tasks/application/searchtasks"
	tasksentities "task-go/src/modules/tasks/domain/entities"
	tasksenums "task-go/src/modules/tasks/domain/enums"
	taskscontrollers "task-go/src/modules/tasks/infrastructure/controllers"
	"task-go/src/modules/users/application/deleteuser"
	"task-go/src/modules/users/application/regenerateuserapitoken"
	usersentities "task-go/src/modules/users/domain/entities"
	usersenums "task-go/src/modules/users/domain/enums"
	userscontrollers "task-go/src/modules/users/infrastructure/controllers"
)

// resolversByType los controllers GraphQL de la peticion en curso (el getResolversByType() de task-php): viajan en
// el context porque el schema se construye una sola vez por proceso.
type resolversByType struct {
	listUsersApiController              *userscontrollers.ListUsersApiController
	getUserApiController                *userscontrollers.GetUserApiController
	createUserApiController             *userscontrollers.CreateUserApiController
	logInUserApiController              *userscontrollers.LogInUserApiController
	updateUserApiController             *userscontrollers.UpdateUserApiController
	deleteUserApiController             *userscontrollers.DeleteUserApiController
	regenerateUserApiTokenApiController *userscontrollers.RegenerateUserApiTokenApiController
	searchTasksApiController            *taskscontrollers.SearchTasksApiController
	getTaskApiController                *taskscontrollers.GetTaskApiController
	createTaskApiController             *taskscontrollers.CreateTaskApiController
	updateTaskApiController             *taskscontrollers.UpdateTaskApiController
	deleteTaskApiController             *taskscontrollers.DeleteTaskApiController
}

type resolversByTypeKey struct{}

// argumentos de schema.graphql. graph-gophers casa user_uuid con UserUuid (sin guiones bajos y sin mayusculas).
type (
	userUuidArgs struct {
		UserUuid graphqlgo.ID
	}
	taskUuidArgs struct {
		TaskUuid graphqlgo.ID
	}
	tasksFilterInput struct {
		TaskText       *string
		TaskStatus     *string
		TaskType       *string
		TargetDateFrom *string
		TargetDateTo   *string
	}
	// un argumento con default (page: Int = 1) llega siempre: graph-gophers lo trata como no nulo
	tasksArgs struct {
		Filter   *tasksFilterInput
		Page     int32
		PageSize int32
	}
	userTasksArgs struct {
		UserUuid graphqlgo.ID
		Filter   *tasksFilterInput
		Page     int32
		PageSize int32
	}
	createUserArgs struct {
		UserName  string
		UserEmail string
		Password  string
	}
	logInArgs struct {
		UserName string
		Password string
	}
	updateUserArgs struct {
		UserUuid  graphqlgo.ID
		UserName  string
		UserEmail string
	}
	createTaskArgs struct {
		TaskTitle       string
		TaskStatus      *string
		TaskType        *string
		TargetDate      *string
		TaskDescription *string
	}
	updateTaskArgs struct {
		TaskUuid        graphqlgo.ID
		TaskTitle       string
		TaskStatus      *string
		TaskType        *string
		TargetDate      *string
		TaskDescription *string
	}
)

// rootResolver tipos Query y Mutation de schema.graphql: cada campo pasa sus argumentos, como el $args de task-php,
// al controller de su caso de uso (uno por campo: <CasoDeUso>ApiController.Invoke).
type rootResolver struct{}

func (r *rootResolver) Users(ctx context.Context) ([]*userResolver, error) {
	users, err := getResolversByType(ctx).listUsersApiController.Invoke()
	if err != nil {
		return nil, err
	}
	userResolvers := []*userResolver{}
	for _, user := range users {
		userResolvers = append(userResolvers, &userResolver{user: user})
	}
	return userResolvers, nil
}

func (r *rootResolver) User(ctx context.Context, args userUuidArgs) (*userResolver, error) {
	user, err := getResolversByType(ctx).getUserApiController.Invoke(map[string]any{
		usersenums.UsersKeyEnum.USER_UUID: string(args.UserUuid),
	})
	if err != nil {
		return nil, err
	}
	return &userResolver{user: *user}, nil
}

func (r *rootResolver) Tasks(ctx context.Context, args tasksArgs) (*taskPageResolver, error) {
	return getTaskPageResolver(getResolversByType(ctx).searchTasksApiController.Invoke("", args.toArgs()))
}

func (r *rootResolver) Task(ctx context.Context, args taskUuidArgs) (*taskResolver, error) {
	return getTaskResolver(getResolversByType(ctx).getTaskApiController.Invoke(map[string]any{
		tasksenums.TasksKeyEnum.TASK_UUID: string(args.TaskUuid),
	}))
}

func (r *rootResolver) UserTasks(ctx context.Context, args userTasksArgs) (*taskPageResolver, error) {
	return getTaskPageResolver(getResolversByType(ctx).searchTasksApiController.Invoke(
		string(args.UserUuid),
		tasksArgs{Filter: args.Filter, Page: args.Page, PageSize: args.PageSize}.toArgs(),
	))
}

func (r *rootResolver) CreateUser(ctx context.Context, args createUserArgs) (*userResolver, error) {
	return getUserWithApiTokenResolver(getResolversByType(ctx).createUserApiController.Invoke(map[string]any{
		usersenums.UsersKeyEnum.USER_NAME:  args.UserName,
		usersenums.UsersKeyEnum.USER_EMAIL: args.UserEmail,
		usersenums.UsersKeyEnum.PASSWORD:   args.Password,
	}))
}

func (r *rootResolver) LogIn(ctx context.Context, args logInArgs) (*userResolver, error) {
	return getUserWithApiTokenResolver(getResolversByType(ctx).logInUserApiController.Invoke(map[string]any{
		usersenums.UsersKeyEnum.USER_NAME: args.UserName,
		usersenums.UsersKeyEnum.PASSWORD:  args.Password,
	}))
}

func (r *rootResolver) UpdateUser(ctx context.Context, args updateUserArgs) (*userResolver, error) {
	user, err := getResolversByType(ctx).updateUserApiController.Invoke(map[string]any{
		usersenums.UsersKeyEnum.USER_UUID:  string(args.UserUuid),
		usersenums.UsersKeyEnum.USER_NAME:  args.UserName,
		usersenums.UsersKeyEnum.USER_EMAIL: args.UserEmail,
	})
	if err != nil {
		return nil, err
	}
	return &userResolver{user: *user}, nil
}

func (r *rootResolver) DeleteUser(ctx context.Context, args userUuidArgs) (*deleteUserResultResolver, error) {
	deleteUserResult, err := getResolversByType(ctx).deleteUserApiController.Invoke(map[string]any{
		usersenums.UsersKeyEnum.USER_UUID: string(args.UserUuid),
	})
	if err != nil {
		return nil, err
	}
	return &deleteUserResultResolver{deleteUserResult: *deleteUserResult}, nil
}

func (r *rootResolver) RegenerateApiToken(ctx context.Context, args userUuidArgs) (*apiTokenResultResolver, error) {
	apiTokenResult, err := getResolversByType(ctx).regenerateUserApiTokenApiController.Invoke(map[string]any{
		usersenums.UsersKeyEnum.USER_UUID: string(args.UserUuid),
	})
	if err != nil {
		return nil, err
	}
	return &apiTokenResultResolver{apiTokenResult: *apiTokenResult}, nil
}

func (r *rootResolver) CreateTask(ctx context.Context, args createTaskArgs) (*taskResolver, error) {
	return getTaskResolver(getResolversByType(ctx).createTaskApiController.Invoke(map[string]any{
		tasksenums.TasksKeyEnum.TASK_TITLE:       args.TaskTitle,
		tasksenums.TasksKeyEnum.TASK_STATUS:      args.TaskStatus,
		tasksenums.TasksKeyEnum.TASK_TYPE:        args.TaskType,
		tasksenums.TasksKeyEnum.TARGET_DATE:      args.TargetDate,
		tasksenums.TasksKeyEnum.TASK_DESCRIPTION: args.TaskDescription,
	}))
}

func (r *rootResolver) UpdateTask(ctx context.Context, args updateTaskArgs) (*taskResolver, error) {
	return getTaskResolver(getResolversByType(ctx).updateTaskApiController.Invoke(map[string]any{
		tasksenums.TasksKeyEnum.TASK_UUID:        string(args.TaskUuid),
		tasksenums.TasksKeyEnum.TASK_TITLE:       args.TaskTitle,
		tasksenums.TasksKeyEnum.TASK_STATUS:      args.TaskStatus,
		tasksenums.TasksKeyEnum.TASK_TYPE:        args.TaskType,
		tasksenums.TasksKeyEnum.TARGET_DATE:      args.TargetDate,
		tasksenums.TasksKeyEnum.TASK_DESCRIPTION: args.TaskDescription,
	}))
}

func (r *rootResolver) DeleteTask(ctx context.Context, args taskUuidArgs) (*deleteTaskResultResolver, error) {
	deleteTaskResult, err := getResolversByType(ctx).deleteTaskApiController.Invoke(map[string]any{
		tasksenums.TasksKeyEnum.TASK_UUID: string(args.TaskUuid),
	})
	if err != nil {
		return nil, err
	}
	return &deleteTaskResultResolver{deleteTaskResult: *deleteTaskResult}, nil
}

// toArgs el $args de task-php: {filter?: {...}, page?, page_size?}. graph-gophers ya aplica los defaults del schema.
func (a tasksArgs) toArgs() map[string]any {
	args := map[string]any{}
	if a.Filter != nil {
		args[tasksenums.TasksKeyEnum.FILTER] = map[string]any{
			tasksenums.TasksKeyEnum.TASK_TEXT:        a.Filter.TaskText,
			tasksenums.TasksKeyEnum.TASK_STATUS:      a.Filter.TaskStatus,
			tasksenums.TasksKeyEnum.TASK_TYPE:        a.Filter.TaskType,
			tasksenums.TasksKeyEnum.TARGET_DATE_FROM: a.Filter.TargetDateFrom,
			tasksenums.TasksKeyEnum.TARGET_DATE_TO:   a.Filter.TargetDateTo,
		}
	}
	args[tasksenums.TasksKeyEnum.PAGE] = int(a.Page)
	args[tasksenums.TasksKeyEnum.PAGE_SIZE] = int(a.PageSize)
	return args
}

// userResolver tipo User. api_token solo en createUser y logIn.
type userResolver struct {
	user     usersentities.UserPrimitives
	apiToken *string
}

func (u *userResolver) UserUuid() graphqlgo.ID {
	return graphqlgo.ID(u.user.UserUuid)
}

func (u *userResolver) UserName() string {
	return u.user.UserName
}

func (u *userResolver) UserEmail() string {
	return u.user.UserEmail
}

func (u *userResolver) CreatedAt() string {
	return u.user.CreatedAt
}

func (u *userResolver) UpdatedAt() string {
	return u.user.UpdatedAt
}

func (u *userResolver) ApiToken() *string {
	return u.apiToken
}

// Tasks User.tasks: las tasks paginadas de ese usuario (el resolver de tipo User de task-php).
func (u *userResolver) Tasks(ctx context.Context, args tasksArgs) (*taskPageResolver, error) {
	return getTaskPageResolver(getResolversByType(ctx).searchTasksApiController.Invoke(u.user.UserUuid, args.toArgs()))
}

func getUserWithApiTokenResolver(user *usersentities.UserWithApiTokenPrimitives, err error) (*userResolver, error) {
	if err != nil {
		return nil, err
	}
	return &userResolver{user: user.UserPrimitives, apiToken: &user.ApiToken}, nil
}

// taskResolver tipo Task.
type taskResolver struct {
	task tasksentities.TaskPrimitives
}

func (t *taskResolver) TaskId() int32 {
	return int32(t.task.TaskId)
}

func (t *taskResolver) TaskUuid() graphqlgo.ID {
	return graphqlgo.ID(t.task.TaskUuid)
}

func (t *taskResolver) UserUuid() graphqlgo.ID {
	return graphqlgo.ID(t.task.UserUuid)
}

func (t *taskResolver) TaskTitle() string {
	return t.task.TaskTitle
}

func (t *taskResolver) TaskStatus() string {
	return t.task.TaskStatus
}

func (t *taskResolver) TaskType() string {
	return t.task.TaskType
}

func (t *taskResolver) TargetDate() *string {
	return t.task.TargetDate
}

func (t *taskResolver) TaskDescription() *string {
	return t.task.TaskDescription
}

func (t *taskResolver) CreatedAt() string {
	return t.task.CreatedAt
}

func (t *taskResolver) UpdatedAt() string {
	return t.task.UpdatedAt
}

func getTaskResolver(task *tasksentities.TaskPrimitives, err error) (*taskResolver, error) {
	if err != nil {
		return nil, err
	}
	return &taskResolver{task: *task}, nil
}

// taskPageResolver tipo TaskPage.
type taskPageResolver struct {
	taskPage searchtasks.SearchTasksResultPrimitives
}

func (p *taskPageResolver) Items() []*taskResolver {
	taskResolvers := []*taskResolver{}
	for _, task := range p.taskPage.Items {
		taskResolvers = append(taskResolvers, &taskResolver{task: task})
	}
	return taskResolvers
}

func (p *taskPageResolver) Page() int32 {
	return int32(p.taskPage.Page)
}

func (p *taskPageResolver) PageSize() int32 {
	return int32(p.taskPage.PageSize)
}

func (p *taskPageResolver) TotalItems() int32 {
	return int32(p.taskPage.TotalItems)
}

func (p *taskPageResolver) TotalPages() int32 {
	return int32(p.taskPage.TotalPages)
}

func getTaskPageResolver(taskPage *searchtasks.SearchTasksResultPrimitives, err error) (*taskPageResolver, error) {
	if err != nil {
		return nil, err
	}
	return &taskPageResolver{taskPage: *taskPage}, nil
}

// deleteUserResultResolver tipo DeleteUserResult.
type deleteUserResultResolver struct {
	deleteUserResult deleteuser.DeleteUserResultPrimitives
}

func (d *deleteUserResultResolver) UserUuid() graphqlgo.ID {
	return graphqlgo.ID(d.deleteUserResult.UserUuid)
}

func (d *deleteUserResultResolver) Affected() int32 {
	return int32(d.deleteUserResult.Affected)
}

// apiTokenResultResolver tipo ApiTokenResult.
type apiTokenResultResolver struct {
	apiTokenResult regenerateuserapitoken.RegenerateUserApiTokenResultPrimitives
}

func (a *apiTokenResultResolver) UserUuid() graphqlgo.ID {
	return graphqlgo.ID(a.apiTokenResult.UserUuid)
}

func (a *apiTokenResultResolver) ApiToken() string {
	return a.apiTokenResult.ApiToken
}

// deleteTaskResultResolver tipo DeleteTaskResult.
type deleteTaskResultResolver struct {
	deleteTaskResult deletetask.DeleteTaskResultPrimitives
}

func (d *deleteTaskResultResolver) TaskUuid() graphqlgo.ID {
	return graphqlgo.ID(d.deleteTaskResult.TaskUuid)
}

func (d *deleteTaskResultResolver) Affected() int32 {
	return int32(d.deleteTaskResult.Affected)
}

func getResolversByType(ctx context.Context) *resolversByType {
	return ctx.Value(resolversByTypeKey{}).(*resolversByType)
}
