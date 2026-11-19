class TaskStatusEnum:
    """
    Valores de tasks.task_status (los mismos que el CHECK de la migracion create-tasks-table).
    """

    PENDING = "pending"
    IN_PROGRESS = "in_progress"
    DONE = "done"

    DEFAULT = "pending"

    VALUES = ("pending", "in_progress", "done")
