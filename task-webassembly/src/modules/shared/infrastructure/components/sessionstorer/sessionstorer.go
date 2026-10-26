package sessionstorer

import (
	"encoding/json"
	"syscall/js"

	"task-webassembly/src/modules/shared/application"
	"task-webassembly/src/modules/shared/infrastructure/components/jsvaluer"
)

const SESSION_KEY = "task-webassembly-session"

// SessionStorer sesion del usuario en sessionStorage (se borra al cerrar la pestaña). Como todo componente no
// captura: si el navegador bloquea sessionStorage el error sube al controller.
// La sesion es {apiToken, userUuid, userName, userEmail}.
type SessionStorer struct {
	jsvaluer *jsvaluer.Jsvaluer
}

func GetInstance() *SessionStorer {
	return &SessionStorer{
		jsvaluer: jsvaluer.GetInstance(),
	}
}

// GetSession nil si no hay sesion (o no tiene apiToken).
func (s *SessionStorer) GetSession() (map[string]any, error) {
	var sessionJson js.Value
	if err := s.jsvaluer.Catch(func() {
		sessionJson = js.Global().Get("sessionStorage").Call("getItem", SESSION_KEY)
	}); err != nil {
		return nil, err
	}
	if sessionJson.Type() != js.TypeString {
		return nil, nil
	}

	var session map[string]any
	if err := json.Unmarshal([]byte(sessionJson.String()), &session); err != nil {
		return nil, err
	}
	if application.GetTrimmedString(session, "apiToken") == "" {
		return nil, nil
	}
	return session, nil
}

func (s *SessionStorer) GetApiToken() (string, error) {
	session, err := s.GetSession()
	return application.GetTrimmedString(session, "apiToken"), err
}

func (s *SessionStorer) GetUserUuid() (string, error) {
	session, err := s.GetSession()
	return application.GetTrimmedString(session, "userUuid"), err
}

func (s *SessionStorer) SaveSession(session map[string]any) error {
	sessionJson, err := json.Marshal(session)
	if err != nil {
		return err
	}
	return s.jsvaluer.Catch(func() {
		js.Global().Get("sessionStorage").Call("setItem", SESSION_KEY, string(sessionJson))
	})
}

func (s *SessionStorer) RemoveSession() error {
	return s.jsvaluer.Catch(func() {
		js.Global().Get("sessionStorage").Call("removeItem", SESSION_KEY)
	})
}
