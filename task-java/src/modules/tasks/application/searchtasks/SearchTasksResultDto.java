package modules.tasks.application.searchtasks;

import java.util.List;
import java.util.Map;

import modules.shared.application.Primitives;
import modules.tasks.domain.enums.TasksKeyEnum;

import static modules.shared.application.Primitives.getInt;

public final class SearchTasksResultDto {

    // la pagina pedida: lista de TaskEntity.toPrimitives
    private final List<Map<String, Object>> tasks;
    private final int page;
    private final int pageSize;
    private final int totalItems;
    private final int totalPages;

    private SearchTasksResultDto(Map<String, Object> primitives) {
        this.tasks = Primitives.getPrimitivesList(primitives.get("tasks"));
        this.page = getInt(primitives.get("page"));
        this.pageSize = getInt(primitives.get("pageSize"));
        this.totalItems = getInt(primitives.get("totalItems"));
        this.totalPages = getInt(primitives.get("totalPages"));
    }

    public static SearchTasksResultDto fromPrimitives(Map<String, Object> primitives) {
        return new SearchTasksResultDto(primitives);
    }

    public List<Map<String, Object>> getTasks() {
        return tasks;
    }

    public int getPage() {
        return page;
    }

    public int getPageSize() {
        return pageSize;
    }

    public int getTotalItems() {
        return totalItems;
    }

    public int getTotalPages() {
        return totalPages;
    }

    /**
     * Tipo TaskPage de tasks.graphql.
     */
    public Map<String, Object> toArray() {
        return Primitives.of(
            TasksKeyEnum.ITEMS, tasks,
            TasksKeyEnum.PAGE, page,
            TasksKeyEnum.PAGE_SIZE, pageSize,
            TasksKeyEnum.TOTAL_ITEMS, totalItems,
            TasksKeyEnum.TOTAL_PAGES, totalPages
        );
    }
}
