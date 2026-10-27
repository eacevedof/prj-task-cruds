package testcase;

import java.util.Collection;
import java.util.Map;

import org.junit.jupiter.api.Assertions;
import org.junit.jupiter.api.BeforeEach;

import modules.shared.infrastructure.components.httprequester.HttpRequestContext;
import modules.shared.infrastructure.components.httpresponder.HttpResponder;
import modules.shared.infrastructure.repositories.JsonResponseRepositoryMock;

/**
 * El TestCase de PHPUnit sobre JUnit: una clase por fichero, un test por cada metodo test*, cada uno con su
 * instancia y su setUp(), y las aserciones con el nombre de PHPUnit.
 */
public abstract class AbstractTestCase {

    /**
     * Los controllers leen la peticion en curso (HttpRequestContext): cada test parte de una vacia.
     */
    @BeforeEach
    protected void setUp() {
        HttpRequestContext httpRequestContext = HttpRequestContext.getInstance();
        httpRequestContext.loadHttpRequest(httpRequestContext.getEmptyHttpRequest());
    }

    protected void dResponseUc(HttpResponder httpResponse, String useCase) {
        JsonResponseRepositoryMock.fromHttpResponse(httpResponse).getDebug(useCase);
    }

    protected void dResponse(HttpResponder httpResponse) {
        JsonResponseRepositoryMock.fromHttpResponse(httpResponse).getDebug();
    }

    protected void assertEquals(Object expected, Object actual) {
        Assertions.assertEquals(expected, actual);
    }

    protected void assertEquals(Object expected, Object actual, String message) {
        Assertions.assertEquals(expected, actual, message);
    }

    protected void assertSame(Object expected, Object actual) {
        Assertions.assertEquals(expected, actual);
    }

    protected void assertNotEquals(Object notExpected, Object actual) {
        Assertions.assertNotEquals(notExpected, actual);
    }

    /**
     * empty() de PHP: null, "", 0, false, [] y {}.
     */
    protected void assertEmpty(Object actual) {
        Assertions.assertTrue(isEmpty(actual), "expected empty, got " + actual);
    }

    protected void assertNotEmpty(Object actual) {
        Assertions.assertFalse(isEmpty(actual), "expected not empty, got " + actual);
    }

    protected void assertNull(Object actual) {
        Assertions.assertNull(actual);
    }

    protected void assertContains(Object needle, Collection<?> haystack) {
        Assertions.assertTrue(haystack.contains(needle), "expected " + needle + " in " + haystack);
    }

    protected void assertCount(int expectedCount, Collection<?> haystack) {
        Assertions.assertEquals(expectedCount, haystack.size());
    }

    protected void assertArrayNotHasKey(String key, Map<String, ?> array) {
        Assertions.assertFalse(array.containsKey(key), "did not expect key " + key);
    }

    protected void assertInstanceOf(Class<?> expectedClass, Object actual) {
        Assertions.assertInstanceOf(expectedClass, actual);
    }

    protected void assertStringContainsString(String needle, String haystack) {
        Assertions.assertTrue(haystack.contains(needle), "expected \"" + needle + "\" in \"" + haystack + "\"");
    }

    protected void assertStringNotContainsString(String needle, String haystack) {
        Assertions.assertFalse(haystack.contains(needle), "did not expect \"" + needle + "\" in the text");
    }

    protected void assertStringStartsWith(String prefix, String text) {
        Assertions.assertTrue(text.startsWith(prefix), "expected \"" + text + "\" to start with \"" + prefix + "\"");
    }

    private boolean isEmpty(Object actual) {
        if (actual == null || "".equals(actual) || Boolean.FALSE.equals(actual)) return true;
        if (actual instanceof Number number) return number.longValue() == 0 && number.doubleValue() == 0;
        if (actual instanceof Collection<?> collection) return collection.isEmpty();
        if (actual instanceof Map<?, ?> map) return map.isEmpty();
        return false;
    }
}
