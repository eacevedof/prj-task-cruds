<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Tasks\Application\ListTasks\ListTasksDto;
use App\Modules\Tasks\Application\ListTasks\ListTasksService;
use App\Modules\Tasks\Domain\Exceptions\TasksException;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * GET /api/v1/tasks  (solo las del usuario del token)
 */
final class ListTasksController extends AbstractApiController
{
    public function index(): HttpResponder
    {
        try {
            $listTasksResultDto = ListTasksService::getInstance()->__invoke(
                ListTasksDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
                ])
            );
            return $this->respondOk($listTasksResultDto->toArray());
        }
        catch (UsersException | TasksException $e) {
            return $this->respondDomainError($e);
        }
        catch (Throwable $e) {
            return $this->respondUnexpectedError($e, __METHOD__);
        }
    }
}
