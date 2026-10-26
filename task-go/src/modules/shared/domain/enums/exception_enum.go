package enums

// ExceptionEnum codigos HTTP que usa la app (el catalogo completo de task-php se reduce a los que se usan).
var ExceptionEnum = struct {
	CODE_OK                    int
	CODE_NO_CONTENT            int
	CODE_BAD_REQUEST           int
	CODE_UNAUTHORIZED          int
	CODE_NOT_FOUND             int
	CODE_METHOD_NOT_ALLOWED    int
	CODE_CONFLICT              int
	CODE_INTERNAL_SERVER_ERROR int
}{
	CODE_OK:                    200,
	CODE_NO_CONTENT:            204,
	CODE_BAD_REQUEST:           400,
	CODE_UNAUTHORIZED:          401,
	CODE_NOT_FOUND:             404,
	CODE_METHOD_NOT_ALLOWED:    405,
	CODE_CONFLICT:              409,
	CODE_INTERNAL_SERVER_ERROR: 500,
}
