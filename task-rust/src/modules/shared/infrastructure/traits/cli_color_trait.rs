/// colores ANSI para la consola: get_xxx() devuelve el texto coloreado para componer una linea, echo_xxx() la imprime
/// entera por stdout. Sin dieRed: el codigo de salida lo pone AbstractConsoleCommand (1 dominio, 2 infraestructura).
/// Solo metodos por defecto: quien lo implementa no tiene que aportar nada.
pub trait CliColorTrait {
    fn echo_red(&self, text: &str) {
        println!("{}", self.get_red(text));
    }

    fn get_red(&self, text: &str) -> String {
        format!("\x1b[91m{text}\x1b[0m")
    }

    fn echo_green(&self, text: &str) {
        println!("{}", self.get_green(text));
    }

    fn get_green(&self, text: &str) -> String {
        format!("\x1b[92m{text}\x1b[0m")
    }

    fn echo_yellow(&self, text: &str) {
        println!("{}", self.get_yellow(text));
    }

    fn get_yellow(&self, text: &str) -> String {
        format!("\x1b[93m{text}\x1b[0m")
    }

    fn echo_blue(&self, text: &str) {
        println!("{}", self.get_blue(text));
    }

    fn get_blue(&self, text: &str) -> String {
        format!("\x1b[94m{text}\x1b[0m")
    }

    fn echo_white(&self, text: &str) {
        println!("{}", self.get_white(text));
    }

    fn get_white(&self, text: &str) -> String {
        format!("\x1b[97m{text}\x1b[0m")
    }

    fn echo_orange(&self, text: &str) {
        println!("{}", self.get_orange(text));
    }

    fn get_orange(&self, text: &str) -> String {
        format!("\x1b[38;5;214m{text}\x1b[0m")
    }
}
