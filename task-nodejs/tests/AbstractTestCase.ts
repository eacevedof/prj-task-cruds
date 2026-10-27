import assert from "node:assert/strict";
import { describe, test } from "node:test";

import type { HttpResponder } from "#app/Modules/Shared/Infrastructure/Components/HttpResponder/HttpResponder.ts";
import { HttpRequestContext } from "#app/Modules/Shared/Infrastructure/Components/HttpRequester/HttpRequestContext.ts";

import { JsonResponseRepositoryMock } from "#tests/Modules/Shared/Infrastructure/Repositories/JsonResponseRepositoryMock.ts";

type TestCaseClass = {
    name: string;
    prototype: AbstractTestCase;
    new (): AbstractTestCase;
    getDataProviders?: () => Record<string, () => Record<string, unknown[]>>;
};

/**
 * El TestCase de PHPUnit sobre node:test: una clase por fichero, un test por cada metodo test*, cada uno con su
 * instancia y su setUp(), y las aserciones con el nombre de PHPUnit.
 */
export abstract class AbstractTestCase {

    /**
     * Registra los tests de la clase. getDataProviders() hace de #[DataProvider]: {testX: () => {caso: [args]}} lanza
     * testX una vez por caso, con sus args.
     */
    static runTests(testCaseClass: TestCaseClass): void {
        describe(testCaseClass.name, () => {
            const dataProviders = testCaseClass.getDataProviders?.() ?? {};
            for (const methodName of Object.getOwnPropertyNames(testCaseClass.prototype)) {
                if (!methodName.startsWith("test")) continue;

                const testCases = dataProviders[methodName]?.() ?? { "": [] };
                for (const [testCaseName, testCaseArgs] of Object.entries(testCases)) {
                    test(testCaseName ? `${methodName} with data set "${testCaseName}"` : methodName, () => {
                        const testCase = new testCaseClass();
                        testCase.setUp();
                        const testMethod = Reflect.get(testCase, methodName) as (...args: unknown[]) => void;
                        testMethod.apply(testCase, testCaseArgs);
                    });
                }
            }
        });
    }

    /**
     * Los controllers leen la peticion en curso (HttpRequestContext): cada test parte de una vacia.
     */
    protected setUp(): void {
        const httpRequestContext = HttpRequestContext.getInstance();
        httpRequestContext.loadHttpRequest(httpRequestContext.getEmptyHttpRequest());
    }

    protected dResponseUc(httpResponse: HttpResponder, useCase: string): void {
        JsonResponseRepositoryMock.fromHttpResponse(httpResponse).getDebug(useCase);
    }

    protected dResponse(httpResponse: HttpResponder): void {
        JsonResponseRepositoryMock.fromHttpResponse(httpResponse).getDebug();
    }

    protected assertEquals(expected: unknown, actual: unknown, message: string = ""): void {
        assert.deepStrictEqual(actual, expected, message || undefined);
    }

    protected assertSame(expected: unknown, actual: unknown, message: string = ""): void {
        assert.deepStrictEqual(actual, expected, message || undefined);
    }

    protected assertNotEquals(notExpected: unknown, actual: unknown): void {
        assert.notDeepStrictEqual(actual, notExpected);
    }

    /**
     * empty() de PHP: null, "", 0, false, [] y {}.
     */
    protected assertEmpty(actual: unknown): void {
        assert.ok(this.#isEmpty(actual), `expected empty, got ${JSON.stringify(actual)}`);
    }

    protected assertNotEmpty(actual: unknown): void {
        assert.ok(!this.#isEmpty(actual), `expected not empty, got ${JSON.stringify(actual)}`);
    }

    protected assertNull(actual: unknown): void {
        assert.equal(actual, null);
    }

    protected assertContains(needle: unknown, haystack: unknown[]): void {
        assert.ok(haystack.includes(needle), `expected ${JSON.stringify(needle)} in ${JSON.stringify(haystack)}`);
    }

    protected assertCount(expectedCount: number, haystack: unknown[]): void {
        assert.equal(haystack.length, expectedCount);
    }

    protected assertArrayNotHasKey(key: string, array: object): void {
        assert.ok(!(key in array), `did not expect key ${key}`);
    }

    // Function y no un constructor: una clase con constructor privado (HttpResponder) tambien vale
    protected assertInstanceOf(expectedClass: Function, actual: unknown): void {
        assert.ok(actual instanceof expectedClass, `expected an instance of ${expectedClass.name}`);
    }

    protected assertStringContainsString(needle: string, haystack: string): void {
        assert.ok(haystack.includes(needle), `expected ${JSON.stringify(needle)} in ${JSON.stringify(haystack)}`);
    }

    protected assertStringNotContainsString(needle: string, haystack: string): void {
        assert.ok(!haystack.includes(needle), `did not expect ${JSON.stringify(needle)} in the text`);
    }

    protected assertStringStartsWith(prefix: string, text: string): void {
        assert.ok(text.startsWith(prefix), `expected ${JSON.stringify(text)} to start with ${JSON.stringify(prefix)}`);
    }

    #isEmpty(actual: unknown): boolean {
        if (actual == null || actual === "" || actual === 0 || actual === false) return true;
        if (Array.isArray(actual)) return !actual.length;
        if (typeof actual === "object") return !Object.keys(actual).length;
        return false;
    }
}
