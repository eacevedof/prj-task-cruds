use serde_json::{Map, Value};

/// Los dto se construyen desde un map de primitivos (el array $primitives de task-php) para que el controller pueda
/// juntar metadata + usuario autenticado + campos de la peticion en un solo map. Con serde_json (preserve_order) las
/// claves salen en el orden en que se insertan, como en un array de PHP.
/// Estas funciones hacen los casts de PHP: (string) y (int) sobre un valor que puede faltar, ser null o venir de otro
/// tipo.
pub type Primitives = Map<String, Value>;

/// un objeto JSON como Primitives: get_primitives(json!({"userUuid": user_uuid})). Cualquier otra cosa da un map vacio.
pub fn get_primitives(value: Value) -> Primitives {
    match value {
        Value::Object(primitives) => primitives,
        _ => Map::new(),
    }
}

/// trim((string) ($primitives[$key] ?? ""))
pub fn get_trimmed_string(primitives: &Primitives, key: &str) -> String {
    to_string(primitives.get(key).unwrap_or(&Value::Null)).trim().to_string()
}

/// (int) ($primitives[$key] ?? 0)
pub fn get_int(primitives: &Primitives, key: &str) -> i64 {
    to_int(primitives.get(key).unwrap_or(&Value::Null))
}

/// (int) ($primitives[$key] ?? $default): null cuenta como ausente.
pub fn get_int_or_default(primitives: &Primitives, key: &str, default_value: i64) -> i64 {
    match primitives.get(key) {
        None | Some(Value::Null) => default_value,
        Some(value) => to_int(value),
    }
}

/// [...$a, ...$b]: con claves de texto, la ultima gana (y la clave se queda en su primera posicion).
pub fn merge_primitives(primitives_list: &[&Primitives]) -> Primitives {
    let mut merged = Map::new();
    for primitives in primitives_list {
        for (key, value) in primitives.iter() {
            merged.insert(key.clone(), value.clone());
        }
    }
    merged
}

/// (string) $value de PHP: true "1", false/null "", 1.0 "1".
pub fn to_string(value: &Value) -> String {
    match value {
        Value::Null => String::new(),
        Value::String(text) => text.clone(),
        Value::Bool(is_true) => {
            if *is_true {
                "1".to_string()
            } else {
                String::new()
            }
        }
        Value::Number(number) => match (number.as_i64(), number.as_f64()) {
            (Some(integer), _) => integer.to_string(),
            (None, Some(float)) if float.fract() == 0.0 && float.abs() < 1e15 => format!("{float:.0}"),
            _ => number.to_string(),
        },
        Value::Array(_) | Value::Object(_) => value.to_string(),
    }
}

/// (int) $value de PHP: "12abc" 12, "abc" 0, 1.9 1, true 1.
pub fn to_int(value: &Value) -> i64 {
    match value {
        Value::Null => 0,
        Value::Bool(is_true) => i64::from(*is_true),
        Value::Number(number) => number.as_i64().unwrap_or_else(|| number.as_f64().unwrap_or(0.0).trunc() as i64),
        _ => get_leading_int(&to_string(value)),
    }
}

fn get_leading_int(text: &str) -> i64 {
    let text = text.trim();
    let end = text
        .char_indices()
        .take_while(|(index, char)| char.is_ascii_digit() || (*index == 0 && (*char == '-' || *char == '+')))
        .count();
    text[..end].parse::<i64>().unwrap_or(0)
}
