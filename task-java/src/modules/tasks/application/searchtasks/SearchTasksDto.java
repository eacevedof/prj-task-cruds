package modules.tasks.application.searchtasks;

import java.util.Map;

import modules.shared.application.AbstractInputDto;
import modules.shared.application.Primitives;
import modules.tasks.domain.services.TasksSearchValidator;

import static modules.shared.application.Primitives.getInt;
import static modules.shared.application.Primitives.getTrimmedString;

public final class SearchTasksDto extends AbstractInputDto {

    private final int authUserId;
    private final String authUserUuid;
    private final String userUuid;
    private final String taskText;
    private final String taskStatus;
    private final String taskType;
    private final String targetDateFrom;
    private final String targetDateTo;
    private final int page;
    private final int pageSize;

    /**
     * userUuid vacio = las del usuario autenticado. Sin page/pageSize: primera pagina con el tamaño por defecto.
     */
    private SearchTasksDto(Map<String, Object> primitives) {
        super(primitives);

        this.authUserId = getInt(primitives.get("authUserId"));
        this.authUserUuid = getTrimmedString(primitives.get("authUserUuid"));
        this.userUuid = getTrimmedString(primitives.get("userUuid"));
        this.taskText = getTrimmedString(primitives.get("taskText"));
        this.taskStatus = getTrimmedString(primitives.get("taskStatus"));
        this.taskType = getTrimmedString(primitives.get("taskType"));
        this.targetDateFrom = getTrimmedString(primitives.get("targetDateFrom"));
        this.targetDateTo = getTrimmedString(primitives.get("targetDateTo"));
        this.page = getInt(Primitives.getValueOrDefault(primitives, "page", TasksSearchValidator.PAGE_MIN));
        this.pageSize = getInt(Primitives.getValueOrDefault(primitives, "pageSize", TasksSearchValidator.PAGE_SIZE_DEFAULT));
    }

    public static SearchTasksDto fromPrimitives(Map<String, Object> primitives) {
        return new SearchTasksDto(primitives);
    }

    public int getAuthUserId() {
        return authUserId;
    }

    public String getAuthUserUuid() {
        return authUserUuid;
    }

    public String getUserUuid() {
        return userUuid;
    }

    public String getTaskText() {
        return taskText;
    }

    public String getTaskStatus() {
        return taskStatus;
    }

    public String getTaskType() {
        return taskType;
    }

    public String getTargetDateFrom() {
        return targetDateFrom;
    }

    public String getTargetDateTo() {
        return targetDateTo;
    }

    public int getPage() {
        return page;
    }

    public int getPageSize() {
        return pageSize;
    }
}
