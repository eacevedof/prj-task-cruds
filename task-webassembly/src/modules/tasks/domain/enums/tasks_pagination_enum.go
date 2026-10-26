package enums

// TasksPaginationEnum paginacion de la lista: tamaño fijo (la API admite de 1 a 100).
var TasksPaginationEnum = struct {
	FIRST_PAGE int
	PAGE_SIZE  int
}{
	FIRST_PAGE: 1,
	PAGE_SIZE:  10,
}
