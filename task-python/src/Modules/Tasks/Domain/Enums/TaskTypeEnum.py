class TaskTypeEnum:
    """
    Valores de tasks.task_type (los mismos que el CHECK de la migracion create-tasks-table).
    """

    PERSONAL = "personal"
    WORK = "work"
    OTHER = "other"

    DEFAULT = "personal"

    VALUES = ("personal", "work", "other")
