package localstorer

import (
	"encoding/json"
	"syscall/js"

	"task-webassembly/src/modules/shared/infrastructure/components/jsvaluer"
)

// LocalStorer preferencias del navegador que sobreviven a recargar (idioma, filtros) en localStorage, como JSON.
// Como todo componente no captura: si el navegador bloquea localStorage el error sube a quien lo usa.
type LocalStorer struct {
	jsvaluer *jsvaluer.Jsvaluer
}

func GetInstance() *LocalStorer {
	return &LocalStorer{
		jsvaluer: jsvaluer.GetInstance(),
	}
}

// GetItem lo guardado, o nil si no hay nada.
func (l *LocalStorer) GetItem(key string) (any, error) {
	var storedJson js.Value
	if err := l.jsvaluer.Catch(func() {
		storedJson = js.Global().Get("localStorage").Call("getItem", key)
	}); err != nil {
		return nil, err
	}
	if storedJson.Type() != js.TypeString {
		return nil, nil
	}

	var item any
	if err := json.Unmarshal([]byte(storedJson.String()), &item); err != nil {
		return nil, err
	}
	return item, nil
}

func (l *LocalStorer) SetItem(key string, value any) error {
	valueJson, err := json.Marshal(value)
	if err != nil {
		return err
	}
	return l.jsvaluer.Catch(func() {
		js.Global().Get("localStorage").Call("setItem", key, string(valueJson))
	})
}

func (l *LocalStorer) RemoveItem(key string) error {
	return l.jsvaluer.Catch(func() {
		js.Global().Get("localStorage").Call("removeItem", key)
	})
}
