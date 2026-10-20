<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Tasks\Application\DeleteTask\DeleteTaskDto;
use App\Modules\Tasks\Application\DeleteTask\DeleteTaskService;
use App\Modules\Tasks\Domain\Exceptions\TasksException;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * DELETE /api/v1/tasks/:taskUuid
 */
final class DeleteTaskController extends AbstractApiController
{
    public function delete(string $taskUuid): HttpResponder
    {
        try {
            $deleteTaskResultDto = DeleteTaskService::getInstance()->__invoke(
                DeleteTaskDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
                    "taskUuid" => $taskUuid,
                ])
            );
            return $this->respondOk($deleteTaskResultDto->toArray());
        }
        catch (UsersException | TasksException $e) {
            return $this->respondDomainError($e);
        }
        catch (Throwable $e) {
            return $this->respondUnexpectedError($e, __METHOD__);
        }
    }
}
