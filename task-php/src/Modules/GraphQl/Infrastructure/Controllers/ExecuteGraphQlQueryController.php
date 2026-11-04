<?php
declare(strict_types=1);

namespace App\Modules\GraphQl\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use App\Modules\Shared\Infrastructure\Controllers\AbstractHttpController;

use App\Modules\GraphQl\Domain\Enums\GraphQlMessageEnum;
use App\Modules\GraphQl\Application\ExecuteGraphQlQuery\ExecuteGraphQlQueryService;
use App\Modules\GraphQl\Application\ExecuteGraphQlQuery\ExecuteGraphQlRequestDto;
use App\Modules\GraphQl\Application\ExecuteGraphQlQuery\ExecuteGraphQlResultDto;


final class ExecuteGraphQlQueryController extends AbstractHttpController
{
    public function invoke(): HttpResponder
    {
        $executeGraphQlRequestDto = ExecuteGraphQlRequestDto::fromPrimitives(
            $this->httpRequest->getPost() ?? []
        );

        if (!$executeGraphQlRequestDto->getQuery()) {
            return $this->getGraphQlResponse(
                ExceptionEnum::CODE_BAD_REQUEST,
                ExecuteGraphQlResultDto::fromErrorMessage(
                    __(GraphQlMessageEnum::GRAPHQL_QUERY_IS_REQUIRED)
                )
            );
        }

        try {
            $executeGraphQlResultDto = ExecuteGraphQlQueryService::getInstance()->invoke(
                $executeGraphQlRequestDto
            );
            return $this->getGraphQlResponse(ExceptionEnum::CODE_OK, $executeGraphQlResultDto);
        }
        catch (Throwable $e) {
            $this->logException($e, __METHOD__);
            return $this->getGraphQlResponse(
                ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
                ExecuteGraphQlResultDto::fromErrorMessage(
                    __(SharedMessageEnum::AN_UNEXPECTED_ERROR_HAS_OCCURRED)
                )
            );
        }
    }

    private function getGraphQlResponse(
        int $httpCode,
        ExecuteGraphQlResultDto $graphqlResultDto
    ): HttpResponder
    {
        return HttpResponder::json(
            (string) json_encode(
                $graphqlResultDto->toArray(),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
            $httpCode
        );
    }
}
