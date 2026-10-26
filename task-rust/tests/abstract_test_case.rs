use std::fmt::Debug;

use serde_json::Value;

use task_rust::modules::shared::application::primitives::Primitives;

// las aserciones de PHPUnit que usan los tests, con el mismo nombre

#[track_caller]
pub fn assert_equals<T: PartialEq + Debug>(expected: T, actual: T, message: &str) {
    assert!(expected == actual, "{message} expected {expected:?}, got {actual:?}");
}

#[track_caller]
pub fn assert_not_equals<T: PartialEq + Debug>(not_expected: T, actual: T) {
    assert!(not_expected != actual, "expected a value other than {not_expected:?}");
}

#[track_caller]
pub fn assert_empty<T: IsEmpty + Debug>(actual: &T) {
    assert!(actual.is_empty_value(), "expected empty, got {actual:?}");
}

#[track_caller]
pub fn assert_not_empty<T: IsEmpty + Debug>(actual: &T) {
    assert!(!actual.is_empty_value(), "expected not empty, got {actual:?}");
}

#[track_caller]
pub fn assert_null(actual: Option<&Value>) {
    assert!(actual.is_none_or(Value::is_null), "expected null, got {actual:?}");
}

#[track_caller]
pub fn assert_contains<T: PartialEq + Debug>(needle: T, haystack: &[T]) {
    assert!(haystack.contains(&needle), "expected {needle:?} in {haystack:?}");
}

#[track_caller]
pub fn assert_string_contains_string(needle: &str, haystack: &str) {
    assert!(haystack.contains(needle), "expected {needle:?} in {haystack:?}");
}

#[track_caller]
pub fn assert_string_not_contains_string(needle: &str, haystack: &str) {
    assert!(!haystack.contains(needle), "did not expect {needle:?} in the text");
}

#[track_caller]
pub fn assert_string_starts_with(prefix: &str, text: &str) {
    assert!(text.starts_with(prefix), "expected {text:?} to start with {prefix:?}");
}

/// el empty() de PHP para lo que se aserta: null, "", [] y {}.
pub trait IsEmpty {
    fn is_empty_value(&self) -> bool;
}

impl IsEmpty for Value {
    fn is_empty_value(&self) -> bool {
        match self {
            Value::Null => true,
            Value::Bool(is_true) => !is_true,
            Value::String(text) => text.is_empty(),
            Value::Array(items) => items.is_empty(),
            Value::Object(object) => object.is_empty(),
            Value::Number(number) => number.as_f64() == Some(0.0),
        }
    }
}

impl IsEmpty for Option<&Value> {
    fn is_empty_value(&self) -> bool {
        self.is_none_or(IsEmpty::is_empty_value)
    }
}

impl<T> IsEmpty for Vec<T> {
    fn is_empty_value(&self) -> bool {
        self.is_empty()
    }
}

impl IsEmpty for Primitives {
    fn is_empty_value(&self) -> bool {
        self.is_empty()
    }
}
