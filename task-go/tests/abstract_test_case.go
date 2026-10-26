package tests

import (
	"reflect"
	"slices"
	"strings"
	"testing"
)

// las aserciones de PHPUnit que usan los tests, con el mismo nombre

func AssertEquals(t *testing.T, expected any, actual any, message ...string) {
	t.Helper()
	if !reflect.DeepEqual(expected, actual) {
		t.Fatalf("%s expected %#v, got %#v", strings.Join(message, " "), expected, actual)
	}
}

func AssertNotEquals(t *testing.T, notExpected any, actual any) {
	t.Helper()
	if reflect.DeepEqual(notExpected, actual) {
		t.Fatalf("expected a value other than %#v", notExpected)
	}
}

func AssertEmpty(t *testing.T, actual any) {
	t.Helper()
	if !isEmpty(actual) {
		t.Fatalf("expected empty, got %#v", actual)
	}
}

func AssertNotEmpty(t *testing.T, actual any) {
	t.Helper()
	if isEmpty(actual) {
		t.Fatalf("expected not empty, got %#v", actual)
	}
}

func AssertNil(t *testing.T, actual any) {
	t.Helper()
	if actual != nil {
		t.Fatalf("expected nil, got %#v", actual)
	}
}

func AssertContains[T comparable](t *testing.T, needle T, haystack []T) {
	t.Helper()
	if !slices.Contains(haystack, needle) {
		t.Fatalf("expected %#v in %#v", needle, haystack)
	}
}

func AssertStringContainsString(t *testing.T, needle string, haystack string) {
	t.Helper()
	if !strings.Contains(haystack, needle) {
		t.Fatalf("expected %q in %q", needle, haystack)
	}
}

func AssertStringNotContainsString(t *testing.T, needle string, haystack string) {
	t.Helper()
	if strings.Contains(haystack, needle) {
		t.Fatalf("did not expect %q in the text", needle)
	}
}

func AssertStringStartsWith(t *testing.T, prefix string, text string) {
	t.Helper()
	if !strings.HasPrefix(text, prefix) {
		t.Fatalf("expected %q to start with %q", text, prefix)
	}
}

func isEmpty(actual any) bool {
	if actual == nil {
		return true
	}
	value := reflect.ValueOf(actual)
	switch value.Kind() {
	case reflect.String, reflect.Slice, reflect.Map, reflect.Array:
		return value.Len() == 0
	case reflect.Pointer, reflect.Interface:
		return value.IsNil()
	}
	return value.IsZero()
}
