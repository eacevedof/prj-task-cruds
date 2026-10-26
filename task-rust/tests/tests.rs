//! lo comun de los tests (el tests/bootstrap.php y el AbstractTestCase de task-php, mas los mocks de
//! tests/Modules/Shared). Cada binario de test lo incluye con #[path = ".../tests.rs"] mod tests; y no todos usan
//! todo: de ahi el allow(dead_code).
#![allow(dead_code)]

#[path = "abstract_test_case.rs"]
pub mod abstract_test_case;

#[path = "bootstrap.rs"]
pub mod bootstrap;

#[path = "modules/mod.rs"]
pub mod modules;
