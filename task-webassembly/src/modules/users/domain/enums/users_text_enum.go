package enums

// UsersTextEnum claves de los textos de la interfaz de users (Translator: TextsEn / TextsEs).
var UsersTextEnum = struct {
	LOGGED_OUT      string
	SESSION_EXPIRED string
}{
	LOGGED_OUT:      "auth.logged_out",
	SESSION_EXPIRED: "auth.session_expired",
}
