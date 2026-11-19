import json
import unittest
from typing import Any

from Modules.Shared.Infrastructure.Components.HttpRequester.HttpRequestContext import HttpRequestContext

from tests.Modules.Shared.Infrastructure.Repositories.JsonResponseRepositoryMock import JsonResponseRepositoryMock


class AbstractTestCase(unittest.TestCase):
    """
    El TestCase de PHPUnit sobre unittest: una clase por fichero, un test por cada metodo test*, cada uno con su
    instancia y su setUp(), y las aserciones con el nombre de PHPUnit.

    getDataProviders() hace de #[DataProvider]: {"testX": callable} donde el callable devuelve {caso: [args]}. Cada caso
    se registra como un test propio llamado `testX with data set "caso"` (como en PHPUnit y en la replica Node).
    """

    @classmethod
    def getDataProviders(cls) -> dict:
        return {}

    def __init_subclass__(cls, **kwargs: Any) -> None:
        super().__init_subclass__(**kwargs)
        for methodName, getTestCases in cls.getDataProviders().items():
            testMethod = cls.__dict__.get(methodName)
            if testMethod is None:
                continue
            delattr(cls, methodName)  # sin args no se puede lanzar: solo existen sus casos
            for testCaseName, testCaseArgs in getTestCases().items():
                setattr(cls, f'{methodName} with data set "{testCaseName}"', cls._buildDataSetTest(testMethod, testCaseArgs))

    @staticmethod
    def _buildDataSetTest(testMethod: Any, testCaseArgs: list) -> Any:
        def dataSetTest(self: "AbstractTestCase") -> None:
            testMethod(self, *testCaseArgs)
        return dataSetTest

    def setUp(self) -> None:
        """
        Los controllers leen la peticion en curso (HttpRequestContext): cada test parte de una vacia.
        """
        httpRequestContext = HttpRequestContext.getInstance()
        httpRequestContext.loadHttpRequest(httpRequestContext.getEmptyHttpRequest())

    def dResponseUc(self, httpResponse: Any, useCase: str) -> None:
        JsonResponseRepositoryMock.fromHttpResponse(httpResponse).getDebug(useCase)

    def dResponse(self, httpResponse: Any) -> None:
        JsonResponseRepositoryMock.fromHttpResponse(httpResponse).getDebug()

    def assertTrue(self, actual: Any, message: str = "") -> None:
        if actual is not True:
            self.fail(message or f"expected true, got {actual!r}")

    def assertFalse(self, actual: Any, message: str = "") -> None:
        if actual is not False:
            self.fail(message or f"expected false, got {actual!r}")

    def assertEquals(self, expected: Any, actual: Any, message: str = "") -> None:
        if not self._isSame(expected, actual):
            self.fail(message or f"expected {expected!r}, got {actual!r}")

    def assertSame(self, expected: Any, actual: Any, message: str = "") -> None:
        self.assertEquals(expected, actual, message)

    def assertNotEquals(self, notExpected: Any, actual: Any) -> None:
        if self._isSame(notExpected, actual):
            self.fail(f"did not expect {actual!r}")

    def assertEmpty(self, actual: Any) -> None:
        """
        empty() de PHP: None, "", 0, False, [] y {}.
        """
        if not self._isEmpty(actual):
            self.fail(f"expected empty, got {self._dump(actual)}")

    def assertNotEmpty(self, actual: Any) -> None:
        if self._isEmpty(actual):
            self.fail(f"expected not empty, got {self._dump(actual)}")

    def assertNull(self, actual: Any) -> None:
        if actual is not None:
            self.fail(f"expected null, got {self._dump(actual)}")

    def assertContains(self, needle: Any, haystack: list) -> None:
        if needle not in haystack:
            self.fail(f"expected {self._dump(needle)} in {self._dump(haystack)}")

    def assertCount(self, expectedCount: int, haystack: Any) -> None:
        if len(haystack) != expectedCount:
            self.fail(f"expected count {expectedCount}, got {len(haystack)}")

    def assertArrayNotHasKey(self, key: str, array: dict) -> None:
        if key in array:
            self.fail(f"did not expect key {key}")

    def assertInstanceOf(self, expectedClass: type, actual: Any) -> None:
        if not isinstance(actual, expectedClass):
            self.fail(f"expected an instance of {expectedClass.__name__}")

    def assertStringContainsString(self, needle: str, haystack: str) -> None:
        if needle not in haystack:
            self.fail(f"expected {self._dump(needle)} in {self._dump(haystack)}")

    def assertStringNotContainsString(self, needle: str, haystack: str) -> None:
        if needle in haystack:
            self.fail(f"did not expect {self._dump(needle)} in the text")

    def assertStringStartsWith(self, prefix: str, text: str) -> None:
        if not text.startswith(prefix):
            self.fail(f"expected {self._dump(text)} to start with {self._dump(prefix)}")

    def _isSame(self, expected: Any, actual: Any) -> bool:
        """
        deepStrictEqual: igualdad profunda y del mismo tipo (un bool no es un int como en Python a secas).
        """
        if isinstance(expected, bool) != isinstance(actual, bool):
            return False
        if isinstance(expected, dict) and isinstance(actual, dict):
            return expected.keys() == actual.keys() and all(self._isSame(expected[k], actual[k]) for k in expected)
        if isinstance(expected, (list, tuple)) and isinstance(actual, (list, tuple)):
            return len(expected) == len(actual) and all(self._isSame(e, a) for e, a in zip(expected, actual))
        return expected == actual

    def _isEmpty(self, actual: Any) -> bool:
        if actual is None or actual == "" or actual is False or (isinstance(actual, (int, float)) and actual == 0):
            return True
        if isinstance(actual, (list, tuple, dict)):
            return not len(actual)
        return False

    def _dump(self, value: Any) -> str:
        return json.dumps(value, default=str, ensure_ascii=False)
