package traits

import "fmt"

// CliColorTrait colores ANSI para la consola: GetXxx() devuelve el texto coloreado para componer una linea,
// EchoXxx() la imprime entera por stdout. Sin dieRed: el codigo de salida lo pone AbstractConsoleCommand
// (1 dominio, 2 infraestructura).
type CliColorTrait struct{}

func (t CliColorTrait) EchoRed(text string) {
	fmt.Println(t.GetRed(text))
}

func (t CliColorTrait) GetRed(text string) string {
	return "\033[91m" + text + "\033[0m"
}

func (t CliColorTrait) EchoGreen(text string) {
	fmt.Println(t.GetGreen(text))
}

func (t CliColorTrait) GetGreen(text string) string {
	return "\033[92m" + text + "\033[0m"
}

func (t CliColorTrait) EchoYellow(text string) {
	fmt.Println(t.GetYellow(text))
}

func (t CliColorTrait) GetYellow(text string) string {
	return "\033[93m" + text + "\033[0m"
}

func (t CliColorTrait) EchoBlue(text string) {
	fmt.Println(t.GetBlue(text))
}

func (t CliColorTrait) GetBlue(text string) string {
	return "\033[94m" + text + "\033[0m"
}

func (t CliColorTrait) EchoWhite(text string) {
	fmt.Println(t.GetWhite(text))
}

func (t CliColorTrait) GetWhite(text string) string {
	return "\033[97m" + text + "\033[0m"
}

func (t CliColorTrait) EchoOrange(text string) {
	fmt.Println(t.GetOrange(text))
}

func (t CliColorTrait) GetOrange(text string) string {
	return "\033[38;5;214m" + text + "\033[0m"
}
