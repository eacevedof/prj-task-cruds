<?php

declare(strict_types=1);

namespace Tests\Modules\Shared\Infrastructure\Components\Http\HttpRequest;

use PHPUnit\Framework\Attributes\DataProvider;

use App\Modules\Shared\Domain\Enums\LanguageCodeEnum;
use App\Modules\Shared\Infrastructure\Components\HttpRequester\HttpRequester;

use App\Modules\Users\Application\GetUser\GetUserDto;

use Tests\AbstractTestCase;
use Tests\Modules\Shared\Infrastructure\Components\Http\HttpRequest\Mothers\{
    RequestMetadataTestCaseEnum,
    RequestMetadataRequestMother
};

/**
 * Cabeceras de la peticion -> metadata (HttpRequester) -> dto de entrada (AbstractInputDto): lo que
 * le llega a la logica de negocio.
 */
final class HttpRequestTest extends AbstractTestCase
{
    public static function getTestCases(): array
    {
        return array_map(
            fn(RequestMetadataTestCaseEnum $requestMetadataTestCaseEnum): array => [$requestMetadataTestCaseEnum],
            array_combine(
                array_column(RequestMetadataTestCaseEnum::cases(), "name"),
                RequestMetadataTestCaseEnum::cases()
            )
        );
    }

    #[DataProvider("getTestCases")]
    public function testMetadataReachesInputDto(RequestMetadataTestCaseEnum $requestMetadataTestCaseEnum): void
    {
        $requestMetadataRequestMother = RequestMetadataRequestMother::getInstance();
        $requestMetadataRequestMother->loadRequestByTestCase($requestMetadataTestCaseEnum);

        $requestMetadata = HttpRequester::getInstance()->getMetadata();
        $expectedMetadata = $requestMetadataRequestMother->getExpectedMetadataByTestCase($requestMetadataTestCaseEnum);
        $this->assertSame($expectedMetadata, $requestMetadata);

        $getUserDto = GetUserDto::fromPrimitives([...$requestMetadata, "userUuid" => "any-uuid"]);
        $this->assertSame($expectedMetadata["userIpAddress"], $getUserDto->getUserIpAddress());
        $this->assertSame($expectedMetadata["userBrowser"], $getUserDto->getUserBrowser());
        $this->assertSame($expectedMetadata["userBrowserVersion"], $getUserDto->getUserBrowserVersion());
        $this->assertSame($expectedMetadata["userOs"], $getUserDto->getUserOs());
        $this->assertSame($expectedMetadata["userTimeZone"], $getUserDto->getUserTimeZone());
        $this->assertSame($expectedMetadata["userLanguageCode"], $getUserDto->getUserLanguageCode());
        $this->assertSame("any-uuid", $getUserDto->getUserUuid());
    }

    public function testDtoWithoutMetadataFallsBackToDefaultLanguage(): void
    {
        // desde CLI o un test no hay peticion: metadata vacia, idioma por defecto
        $getUserDto = GetUserDto::fromPrimitives(["userUuid" => "any-uuid"]);

        $this->assertSame("", $getUserDto->getUserIpAddress());
        $this->assertSame(LanguageCodeEnum::EN, $getUserDto->getUserLanguageCode());
    }
}
