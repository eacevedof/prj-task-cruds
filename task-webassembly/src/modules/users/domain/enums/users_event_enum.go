package enums

var UsersEventEnum = struct {
	LOG_IN_REQUESTED          string
	SIGN_UP_REQUESTED         string
	SESSION_RESTORE_REQUESTED string
	SESSION_STARTED           string
	LOG_OUT_REQUESTED         string
	SESSION_EXPIRED           string

	USER_LOGGED_IN  string
	USER_LOGGED_OUT string
}{
	// {userName, password}
	LOG_IN_REQUESTED: "users.log-in-requested",
	// {userName, userEmail, password}
	SIGN_UP_REQUESTED: "users.sign-up-requested",
	// {apiToken}: al recargar, la sesion guardada se revalida contra la API
	SESSION_RESTORE_REQUESTED: "users.session-restore-requested",
	// {apiToken, userUuid, userName, userEmail}: login, alta o sesion recuperada; StartSessionController entra
	SESSION_STARTED: "users.session-started",
	// {messageKey?}: aviso para la pantalla de entrada (cuenta borrada...)
	LOG_OUT_REQUESTED: "users.log-out-requested",
	// la API respondio 401 (token caducado o rotado en otra pestaña): cualquier modulo lo dispara
	SESSION_EXPIRED: "users.session-expired",

	// {userUuid, userName, userEmail}
	USER_LOGGED_IN:  "users.user-logged-in",
	USER_LOGGED_OUT: "users.user-logged-out",
}
