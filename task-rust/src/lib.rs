//! task-rust: replica 1:1 de task-php (el maestro). Las carpetas de src/ son las de task-php/src en snake_case: un
//! modulo por carpeta y un fichero por clase. Los binarios estan en cmd/ (api = public/index.php, console =
//! console/console.php) y el registro de comandos en console/commands.rs, como en task-php.

// la carpeta y su clase se llaman igual (Components/Logger/Logger.php -> components::logger::logger): es el arbol de
// task-php, no un descuido
#![allow(clippy::module_inception)]

/// el "extends" de PHP: el struct hijo lleva a su padre en un campo y, por Deref, usa sus metodos como propios
/// (el embebido de task-go). extends!(GetUserDto, abstract_input_dto, AbstractInputDto)
#[macro_export]
macro_rules! extends {
    ($child:ty, $field:ident, $parent:ty) => {
        impl std::ops::Deref for $child {
            type Target = $parent;

            fn deref(&self) -> &$parent {
                &self.$field
            }
        }

        impl std::ops::DerefMut for $child {
            fn deref_mut(&mut self) -> &mut $parent {
                &mut self.$field
            }
        }
    };
}

pub mod console;
pub mod core;
pub mod modules;

/// console/commands.php de task-php: fuera de src/, en la misma carpeta que alli
#[path = "../console/commands.rs"]
pub mod commands;
