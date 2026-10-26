package enums

var HttpCodeEnum = struct {
	OK                    int
	CREATED               int
	BAD_REQUEST           int
	UNAUTHORIZED          int
	NOT_FOUND             int
	CONFLICT              int
	INTERNAL_SERVER_ERROR int
}{
	OK:                    200,
	CREATED:               201,
	BAD_REQUEST:           400,
	UNAUTHORIZED:          401,
	NOT_FOUND:             404,
	CONFLICT:              409,
	INTERNAL_SERVER_ERROR: 500,
}
