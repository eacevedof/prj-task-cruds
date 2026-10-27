package modules.tasks.application.searchtasks;

import java.util.List;

import modules.shared.application.Primitives;
import modules.shared.domain.enums.SharedMessageEnum;
import modules.shared.infrastructure.exceptions.FieldsException;
import modules.shared.infrastructure.traits.ErrorStackTrait;
import modules.tasks.domain.entities.TaskEntity;
import modules.tasks.domain.enums.TasksMessageEnum;
import modules.tasks.domain.exceptions.TasksException;
import modules.tasks.domain.services.TasksSearchValidator;
import modules.tasks.infrastructure.repositories.TasksReaderSqliteRepository;

import static core.config.Global.__;

/**
 * Busqueda paginada de las tasks del usuario autenticado (mas reciente primero): filtros por texto, estado,
 * tipo y rango de target_date.
 */
public final class SearchTasksService {

    private final ErrorStackTrait errorStackTrait;
    private final TasksSearchValidator tasksSearchValidator;
    private final TasksReaderSqliteRepository tasksReaderSqliteRepository;

    private SearchTasksDto searchTasksDto;

    private SearchTasksService() {
        this.errorStackTrait = ErrorStackTrait.getInstance();
        this.tasksSearchValidator = TasksSearchValidator.getInstance();
        this.tasksReaderSqliteRepository = TasksReaderSqliteRepository.getInstance();
    }

    public static SearchTasksService getInstance() {
        return new SearchTasksService();
    }

    /**
     * @throws FieldsException
     * @throws TasksException
     */
    public SearchTasksResultDto invoke(SearchTasksDto searchTasksDto) {
        this.searchTasksDto = searchTasksDto;
        failIfWrongInput();
        failIfWrongSearch();

        int userId = searchTasksDto.getAuthUserId();

        int totalItems = tasksReaderSqliteRepository.getTasksCountByUserIdAndFilters(
            userId,
            searchTasksDto.getTaskText(),
            searchTasksDto.getTaskStatus(),
            searchTasksDto.getTaskType(),
            searchTasksDto.getTargetDateFrom(),
            searchTasksDto.getTargetDateTo()
        );
        int page = searchTasksDto.getPage();
        int pageSize = searchTasksDto.getPageSize();
        List<TaskEntity> taskEntities = tasksReaderSqliteRepository.getTasksByUserIdAndFilters(
            userId,
            searchTasksDto.getTaskText(),
            searchTasksDto.getTaskStatus(),
            searchTasksDto.getTaskType(),
            searchTasksDto.getTargetDateFrom(),
            searchTasksDto.getTargetDateTo(),
            pageSize,
            (page - 1) * pageSize
        );

        return SearchTasksResultDto.fromPrimitives(Primitives.of(
            "tasks", taskEntities.stream().map(TaskEntity::toPrimitives).toList(),
            "page", page,
            "pageSize", pageSize,
            "totalItems", totalItems,
            "totalPages", (int) Math.ceil((double) totalItems / pageSize)
        ));
    }

    /**
     * Sin user_uuid se busca en las del autenticado; con uno ajeno responde igual que si no existiera.
     * @throws TasksException
     */
    private void failIfWrongInput() {
        if (searchTasksDto.getAuthUserId() < 1) {
            throw TasksException.unauthorizedCustom(__(TasksMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
        String userUuid = searchTasksDto.getUserUuid().isEmpty()
            ? searchTasksDto.getAuthUserUuid()
            : searchTasksDto.getUserUuid();
        if (!userUuid.equals(searchTasksDto.getAuthUserUuid())) {
            throw TasksException.notFoundCustom(__(TasksMessageEnum.USER_X_NOT_FOUND, userUuid));
        }
    }

    /**
     * @throws FieldsException
     */
    private void failIfWrongSearch() {
        List<String> errors = tasksSearchValidator.getErrors(
            searchTasksDto.getTaskStatus(),
            searchTasksDto.getTaskType(),
            searchTasksDto.getTargetDateFrom(),
            searchTasksDto.getTargetDateTo(),
            searchTasksDto.getPage(),
            searchTasksDto.getPageSize()
        );
        if (errors.isEmpty()) return;

        throw FieldsException.badRequestCustom(
            errorStackTrait.getErrorStackMessage(
                __(SharedMessageEnum.FIELDS_VALIDATION_ERRORS), errors
            ));
    }
}
