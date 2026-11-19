import re

from Modules.Shared.Application.Primitives import getInt
from Modules.Shared.Infrastructure.Repositories.AbstractSqliteRepository import AbstractSqliteRepository

from Modules.Tasks.Domain.Entities.TaskEntity import TaskEntity

# la mas reciente primero en todos los listados
ORDER_BY_NEWEST = "ORDER BY t.id DESC"


class TasksReaderSqliteRepository(AbstractSqliteRepository):

    @staticmethod
    def getInstance() -> "TasksReaderSqliteRepository":
        return TasksReaderSqliteRepository()

    def getTasksByUserIdAndFilters(
        self,
        userId: int,
        taskText: str,
        taskStatus: str,
        taskType: str,
        targetDateFrom: str,
        targetDateTo: str,
        limit: int,
        offset: int,
    ) -> list[TaskEntity]:
        """
        Una pagina de las tasks del usuario que cumplen los filtros (vacio = sin filtro).
        """
        filtersSql, filtersParams = self._getFiltersSqlAndParams(taskText, taskStatus, taskType, targetDateFrom, targetDateTo)
        sql = f"""
        -- getTasksByUserIdAndFilters
        SELECT
            t.id, t.user_id, u.user_uuid, t.task_uuid, t.task_title, t.task_status, t.task_type,
            t.target_date, t.task_description, t.created_at, t.updated_at
        FROM `tasks` t
        INNER JOIN `users` u
        ON u.id = t.user_id
        WHERE 1=1
        AND t.user_id = {userId}
        {filtersSql}
        {ORDER_BY_NEWEST}
        LIMIT {limit} OFFSET {offset}
        """
        return [TaskEntity.fromPrimitives(row) for row in self.query(sql, filtersParams)]

    def getTasksCountByUserIdAndFilters(
        self,
        userId: int,
        taskText: str,
        taskStatus: str,
        taskType: str,
        targetDateFrom: str,
        targetDateTo: str,
    ) -> int:
        filtersSql, filtersParams = self._getFiltersSqlAndParams(taskText, taskStatus, taskType, targetDateFrom, targetDateTo)
        sql = f"""
        -- getTasksCountByUserIdAndFilters
        SELECT
            COUNT(*) AS n_rows
        FROM `tasks` t
        WHERE 1=1
        AND t.user_id = {userId}
        {filtersSql}
        """
        rows = self.query(sql, filtersParams)
        return getInt(rows[0].get("n_rows") if rows and rows[0].get("n_rows") is not None else 0)

    def getTaskById(self, taskId: int) -> TaskEntity | None:
        sql = f"""
        -- getTaskById
        SELECT
            t.id, t.user_id, u.user_uuid, t.task_uuid, t.task_title, t.task_status, t.task_type,
            t.target_date, t.task_description, t.created_at, t.updated_at
        FROM `tasks` t
        INNER JOIN `users` u
        ON u.id = t.user_id
        WHERE 1=1
        AND t.id = {taskId}
        """
        rows = self.query(sql)
        return TaskEntity.fromPrimitives(rows[0]) if rows else None

    def getTaskByTaskUuid(self, taskUuid: str) -> TaskEntity | None:
        sql = """
        -- getTaskByTaskUuid
        SELECT
            t.id, t.user_id, u.user_uuid, t.task_uuid, t.task_title, t.task_status, t.task_type,
            t.target_date, t.task_description, t.created_at, t.updated_at
        FROM `tasks` t
        INNER JOIN `users` u
        ON u.id = t.user_id
        WHERE 1=1
        AND t.task_uuid = ?
        """
        rows = self.query(sql, [taskUuid])
        return TaskEntity.fromPrimitives(rows[0]) if rows else None

    def _getFiltersSqlAndParams(
        self,
        taskText: str,
        taskStatus: str,
        taskType: str,
        targetDateFrom: str,
        targetDateTo: str,
    ) -> tuple[str, list]:
        """
        AND de los filtros que vienen informados, con parametros enlazados (alias t = tasks).
        Texto: en titulo o descripcion, sin distinguir mayusculas (LIKE de SQLite) y con %, _ y \\ escapados
        para que se busquen tal cual. Fechas: sobre target_date, ambos extremos incluidos (NULL no entra).
        """
        conditions: list[str] = []
        params: list = []
        if taskText != "":
            likeText = "%" + re.sub(r"([\\%_])", r"\\\1", taskText) + "%"
            conditions.append("AND (t.task_title LIKE ? ESCAPE '\\' OR IFNULL(t.task_description, '') LIKE ? ESCAPE '\\')")
            params.extend([likeText, likeText])
        if taskStatus != "":
            conditions.append("AND t.task_status = ?")
            params.append(taskStatus)
        if taskType != "":
            conditions.append("AND t.task_type = ?")
            params.append(taskType)
        if targetDateFrom != "":
            conditions.append("AND t.target_date >= ?")
            params.append(targetDateFrom)
        if targetDateTo != "":
            conditions.append("AND t.target_date <= ?")
            params.append(targetDateTo)
        return "\n        ".join(conditions), params
