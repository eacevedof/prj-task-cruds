package datetimer

import (
	"fmt"
	"syscall/js"
)

const MS_PER_DAY = 24 * 60 * 60 * 1000

// DateTimer fechas de calendario en la zona horaria del navegador, en formato YYYY-MM-DD (el de los input date).
// Con el Date de JS y no con time de Go: en wasm time.Local es UTC (no hay base de zonas horarias).
type DateTimer struct{}

func GetInstance() *DateTimer {
	return &DateTimer{}
}

// GetDateFromToday days: dias a sumar a hoy (negativo: hacia atras). YYYY-MM-DD local, no UTC: a las 23:30 en
// Madrid "mañana" es mañana en Madrid.
func (d *DateTimer) GetDateFromToday(days int) string {
	jsDate := js.Global().Get("Date")
	date := jsDate.New(jsDate.Call("now").Float() + float64(days*MS_PER_DAY))
	return fmt.Sprintf("%04d-%02d-%02d", date.Call("getFullYear").Int(), date.Call("getMonth").Int()+1, date.Call("getDate").Int())
}
