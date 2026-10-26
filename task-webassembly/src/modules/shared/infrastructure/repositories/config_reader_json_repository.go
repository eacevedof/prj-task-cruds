package repositories

import (
	"encoding/json"
	"syscall/js"

	"task-webassembly/src/modules/shared/infrastructure/components/jsvaluer"
	"task-webassembly/src/modules/shared/infrastructure/components/promiser"
)

const CONFIG_PATH = "/config.json"

// ConfigReaderJsonRepository configuracion de la SPA: la sirve nginx desde las variables del contenedor (docker/.env).
type ConfigReaderJsonRepository struct {
	jsvaluer *jsvaluer.Jsvaluer
	promiser *promiser.Promiser
}

func ConfigReaderJsonRepositoryGetInstance() *ConfigReaderJsonRepository {
	return &ConfigReaderJsonRepository{
		jsvaluer: jsvaluer.GetInstance(),
		promiser: promiser.GetInstance(),
	}
}

// GetConfig {backends: [{code, name, apiBaseUrl}]}
func (r *ConfigReaderJsonRepository) GetConfig() (map[string]any, error) {
	response, err := r.promiser.Await(js.Global().Call("fetch", CONFIG_PATH, r.jsvaluer.GetJsValue(map[string]any{
		"headers": map[string]any{"Accept": "application/json"},
	})))
	if err != nil {
		return nil, err
	}
	responseText, err := r.promiser.Await(response.Call("text"))
	if err != nil {
		return nil, err
	}

	var config map[string]any
	if err := json.Unmarshal([]byte(responseText.String()), &config); err != nil {
		return nil, err
	}
	return config, nil
}
