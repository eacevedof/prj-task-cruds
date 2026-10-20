<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Tasks\Application\ListUserTasks\ListUserTasksDto;
use App\Modules\Tasks\Application\ListUserTasks\ListUserTasksService;
use App\Modules\Tasks\Domain\Exceptions\TasksException;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * GET /api/v1/users/:userUuid/tasks
 */
final class ListUserTasksController extends AbstractApiController
{
    public function index(string $userUuid): HttpResponder
    {
        try {
            $listUserTasksResultDto = ListUserTasksService::getInstance()->__invoke(
                ListUserTasksDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
                    "userUuid" => $userUuid,
                ])
            );
            return $this->respondOk($listUserTasksResultDto->toArray());
        }
        catch (UsersException | TasksException $e) {
            return $this->respondDomainError($e);
        }
        catch (Throwable $e) {
            return $this->respondUnexpectedError($e, __METHOD__);
        }
    }
}
