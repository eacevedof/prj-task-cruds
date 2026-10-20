<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Domain\Enums;

/**
 * Claves de la API (JSON de entrada y salida, argumentos GraphQL) de tasks, mas las columnas internas
 * con las que se monta TaskEntity.
 */
abstract class TasksKeyEnum
{
    public const string TASK_ID = "task_id";
    public const string TASK_UUID = "task_uuid";
    public const string USER_UUID = "user_uuid";
    public const string TASK_TITLE = "task_title";
    public const string TASK_STATUS = "task_status";
    public const string TASK_TYPE = "task_type";
    public const string TARGET_DATE = "target_date";
    public const string TASK_DESCRIPTION = "task_description";
    public const string CREATED_AT = "created_at";
    public const string UPDATED_AT = "updated_at";
    public const string AFFECTED = "affected";

    // columnas internas de BD: solo para montar TaskEntity, nunca salen por la API
    public const string ID = "id";
    public const string USER_ID = "user_id";

    // busqueda paginada (GraphQL: argumentos de tasks/userTasks y tipo TaskPage)
    public const string FILTER = "filter";
    public const string TASK_TEXT = "task_text";
    public const string TARGET_DATE_FROM = "target_date_from";
    public const string TARGET_DATE_TO = "target_date_to";
    public const string PAGE = "page";
    public const string PAGE_SIZE = "page_size";
    public const string ITEMS = "items";
    public const string TOTAL_ITEMS = "total_items";
    public const string TOTAL_PAGES = "total_pages";
}
