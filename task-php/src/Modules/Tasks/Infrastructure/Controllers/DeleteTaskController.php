<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;
use App\Modules\Shared\Infrastructure\Http\HttpResponse;

use App\Modules\Tasks\Application\DeleteTask\DeleteTaskDto;
use App\Modules\Tasks\Application\DeleteTask\DeleteTaskService;

/**
 * DELETE /api/v1/tasks/:taskUuid
 */
final class DeleteTaskController extends AbstractApiController
{
    public function delete(string $taskUuid): HttpResponse
    {
        $deleteTaskService = null;
        try {
            $deleteTaskService = DeleteTaskService::getInstance();
            $deleteTaskResultDto = $deleteTaskService(DeleteTaskDto::fromPrimitives([
                "taskUuid" => $taskUuid,
            ]));
            return $this->respondOk($deleteTaskResultDto->toArray());
        }
        catch (FieldsException $e) {
            return $this->respondThrowable($e, $deleteTaskService?->getErrors() ?? []);
        }
        catch (Throwable $e) {
            return $this->respondThrowable($e);
        }
    }
}
