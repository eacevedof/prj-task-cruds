package jsvaluer

import (
	"errors"
	"fmt"
	"syscall/js"
)

// Jsvaluer puente entre los valores de Go y los de JS (lo que en task-js no hace falta: alli todo es JS).
// - GetGoValue: objeto/array/primitivo de JS -> map[string]any / []any / string / float64 / bool / nil.
// - GetJsValue: lo contrario; js.ValueOf solo admite []any y map[string]any, no []string ni map[string]string.
// - Catch: el try/catch de JS. Una excepcion de JS (localStorage bloqueado...) llega a Go como panic(js.Error).
type Jsvaluer struct{}

func GetInstance() *Jsvaluer {
	return &Jsvaluer{}
}

func (j *Jsvaluer) GetGoValue(value js.Value) any {
	switch value.Type() {
	case js.TypeString:
		return value.String()
	case js.TypeNumber:
		return value.Float()
	case js.TypeBoolean:
		return value.Bool()
	case js.TypeObject:
		if js.Global().Get("Array").Call("isArray", value).Bool() {
			items := make([]any, value.Length())
			for i := range items {
				items[i] = j.GetGoValue(value.Index(i))
			}
			return items
		}
		keys := js.Global().Get("Object").Call("keys", value)
		object := make(map[string]any, keys.Length())
		for i := 0; i < keys.Length(); i++ {
			key := keys.Index(i).String()
			object[key] = j.GetGoValue(value.Get(key))
		}
		return object
	}
	// undefined, null, funciones, simbolos
	return nil
}

func (j *Jsvaluer) GetJsValue(value any) js.Value {
	switch typedValue := value.(type) {
	case nil:
		return js.Null()
	case js.Value:
		return typedValue
	case map[string]any:
		object := js.Global().Get("Object").New()
		for key, item := range typedValue {
			object.Set(key, j.GetJsValue(item))
		}
		return object
	case map[string]string:
		object := js.Global().Get("Object").New()
		for key, item := range typedValue {
			object.Set(key, item)
		}
		return object
	case []any:
		array := js.Global().Get("Array").New(len(typedValue))
		for i, item := range typedValue {
			array.SetIndex(i, j.GetJsValue(item))
		}
		return array
	case []string:
		array := js.Global().Get("Array").New(len(typedValue))
		for i, item := range typedValue {
			array.SetIndex(i, item)
		}
		return array
	case []map[string]any:
		array := js.Global().Get("Array").New(len(typedValue))
		for i, item := range typedValue {
			array.SetIndex(i, j.GetJsValue(item))
		}
		return array
	}
	return js.ValueOf(value)
}

// GetValues [...arrayLike]: un NodeList (querySelectorAll) o un HTMLCollection como slice.
func (j *Jsvaluer) GetValues(arrayLike js.Value) []js.Value {
	values := make([]js.Value, arrayLike.Length())
	for i := range values {
		values[i] = arrayLike.Index(i)
	}
	return values
}

// Catch ejecuta fn y devuelve como error la excepcion de JS que lance (o el panic de Go).
func (j *Jsvaluer) Catch(fn func()) (err error) {
	defer func() {
		recovered := recover()
		if recovered == nil {
			return
		}
		if jsError, ok := recovered.(js.Error); ok {
			err = errors.New(jsError.Error())
			return
		}
		err = fmt.Errorf("%v", recovered)
	}()
	fn()
	return nil
}
