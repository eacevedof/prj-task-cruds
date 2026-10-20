<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Tasks\Application\GetTask\GetTaskDto;
use App\Modules\Tasks\Application\GetTask\GetTaskService;
use App\Modules\Tasks\Domain\Exceptions\TasksException;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * GET /api/v1/tasks/:taskUuid
 */
final class GetTaskController extends AbstractApiController
{
    public function show(string $taskUuid): HttpResponder
    {
        try {
            $getTaskResultDto = GetTaskService::getInstance()->__invoke(
                GetTaskDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
                    "taskUuid" => $taskUuid,
                ])
            );
            return $this->respondOk($getTaskResultDto->toArray());
        }
        catch (UsersException | TasksException $e) {
            return $this->respondDomainError($e);
        }
        catch (Throwable $e) {
            return $this->respondUnexpectedError($e, __METHOD__);
        }
    }
}
