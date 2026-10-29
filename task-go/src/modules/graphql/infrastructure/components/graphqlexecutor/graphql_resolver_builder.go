package graphqlexecutor

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"reflect"
	"strings"

	graphqlgo "github.com/graph-gophers/graphql-go"
	"github.com/graph-gophers/graphql-go/ast"

	"task-go/src/modules/graphql/domain/enums"
	"task-go/src/modules/graphql/infrastructure/schema"
	"task-go/src/modules/shared/application"
)

// graph-gophers resuelve por reflexion (un metodo o un campo func por cada campo del schema) y no admite la tabla
// tipo => campo => closure de webonyx. resolverBuilder la traduce: por cada tipo objeto del SDL monta en runtime un
// struct (reflect.StructOf) con un campo func por campo GraphQL (tag graphql:"<campo>", UseFieldResolvers). Cada func
// llama al resolver registrado o, si no hay, devuelve el valor del padre con ese nombre (el defaultFieldResolver de
// webonyx). Los valores viajan como map (el ToArray del ResultDto pasado por JSON), igual que el array de task-php.

var (
	contextType = reflect.TypeFor[context.Context]()
	errorType   = reflect.TypeFor[error]()
	scalarTypes = map[string]reflect.Type{
		"Int":     reflect.TypeFor[int32](),
		"Float":   reflect.TypeFor[float64](),
		"String":  reflect.TypeFor[string](),
		"Boolean": reflect.TypeFor[bool](),
		"ID":      reflect.TypeFor[graphqlgo.ID](),
	}
)

// rootResolver Query y Mutation por separado: graph-gophers los pide como metodos del resolver raiz.
type rootResolver struct {
	query    any
	mutation any
}

func (r *rootResolver) Query() any {
	return r.query
}

func (r *rootResolver) Mutation() any {
	return r.mutation
}

type resolverBuilder struct {
	astSchema       *ast.Schema
	resolversByType map[string]map[string]schema.GraphqlResolver
	// el struct de cada tipo objeto: se montan todos al construir; en runtime solo se leen
	objectTypes   map[string]reflect.Type
	buildingTypes map[string]bool
}

func newResolverBuilder(astSchema *ast.Schema, resolversByType map[string]map[string]schema.GraphqlResolver) *resolverBuilder {
	return &resolverBuilder{
		astSchema:       astSchema,
		resolversByType: resolversByType,
		objectTypes:     map[string]reflect.Type{},
		buildingTypes:   map[string]bool{},
	}
}

func (b *resolverBuilder) getRootResolver() (*rootResolver, error) {
	query, err := b.getRootValue(enums.GraphqlEnum.TYPE_QUERY)
	if err != nil {
		return nil, err
	}
	mutation, err := b.getRootValue(enums.GraphqlEnum.TYPE_MUTATION)
	if err != nil {
		return nil, err
	}
	if mutation == nil {
		mutation = query // sin Mutation en el schema: graph-gophers no lo usa, pero no admite nil
	}
	return &rootResolver{query: query, mutation: mutation}, nil
}

func (b *resolverBuilder) getRootValue(rootTypeName string) (any, error) {
	if _, isObject := b.astSchema.Types[rootTypeName].(*ast.ObjectTypeDefinition); !isObject {
		return nil, nil
	}
	if _, err := b.getObjectType(rootTypeName); err != nil {
		return nil, err
	}
	return b.getObjectValue(rootTypeName, map[string]any{}).Interface(), nil
}

// getObjectType struct con un campo func por campo del tipo. Un tipo que se contiene a si mismo no se puede montar
// con reflect.StructOf (no hay tipos recursivos en runtime): hoy ningun tipo del schema lo es.
func (b *resolverBuilder) getObjectType(typeName string) (reflect.Type, error) {
	if objectType, isBuilt := b.objectTypes[typeName]; isBuilt {
		return objectType, nil
	}
	if b.buildingTypes[typeName] {
		return nil, fmt.Errorf("graphql: %s is recursive, the executor does not support it", typeName)
	}
	b.buildingTypes[typeName] = true
	objectDefinition := b.astSchema.Types[typeName].(*ast.ObjectTypeDefinition)
	structFields := []reflect.StructField{}
	for i, fieldDefinition := range objectDefinition.Fields {
		funcType, err := b.getFieldFuncType(fieldDefinition)
		if err != nil {
			return nil, fmt.Errorf("graphql: %s.%s: %w", typeName, fieldDefinition.Name, err)
		}
		structFields = append(structFields, reflect.StructField{
			Name: fmt.Sprintf("F%d", i),
			Type: funcType,
			Tag:  reflect.StructTag(`graphql:"` + fieldDefinition.Name + `"`),
		})
	}
	b.objectTypes[typeName] = reflect.StructOf(structFields)
	return b.objectTypes[typeName], nil
}

// getFieldFuncType func(ctx[, args struct]) (valor, error)
func (b *resolverBuilder) getFieldFuncType(fieldDefinition *ast.FieldDefinition) (reflect.Type, error) {
	inTypes := []reflect.Type{contextType}
	if len(fieldDefinition.Arguments) > 0 {
		argsType, err := getInputValuesType(fieldDefinition.Arguments)
		if err != nil {
			return nil, err
		}
		inTypes = append(inTypes, argsType)
	}
	outType, err := b.getOutputType(fieldDefinition.Type)
	if err != nil {
		return nil, err
	}
	return reflect.FuncOf(inTypes, []reflect.Type{outType, errorType}, false), nil
}

// getOutputType nulable = puntero; un objeto siempre va por puntero.
func (b *resolverBuilder) getOutputType(graphqlType ast.Type) (reflect.Type, error) {
	innerType, isNonNull := unwrapNonNull(graphqlType)
	var goType reflect.Type
	switch typedType := innerType.(type) {
	case *ast.ObjectTypeDefinition:
		objectType, err := b.getObjectType(typedType.Name)
		if err != nil {
			return nil, err
		}
		return reflect.PointerTo(objectType), nil
	case *ast.List:
		elemType, err := b.getOutputType(typedType.OfType)
		if err != nil {
			return nil, err
		}
		goType = reflect.SliceOf(elemType)
	default:
		scalarType, err := getScalarType(innerType)
		if err != nil {
			return nil, err
		}
		goType = scalarType
	}
	if !isNonNull {
		return reflect.PointerTo(goType), nil
	}
	return goType, nil
}

// getInputValuesType struct de argumentos (o de un input): un campo exportado por argumento, que graph-gophers casa
// por nombre sin guiones bajos ni mayusculas (user_uuid -> User_uuid). Con default llega siempre: no es puntero.
func getInputValuesType(inputValues ast.ArgumentsDefinition) (reflect.Type, error) {
	structFields := []reflect.StructField{}
	for _, inputValue := range inputValues {
		goFieldName, err := getGoFieldName(inputValue.Name.Name)
		if err != nil {
			return nil, err
		}
		inputType, err := getInputType(inputValue.Type, inputValue.Default != nil)
		if err != nil {
			return nil, err
		}
		structFields = append(structFields, reflect.StructField{Name: goFieldName, Type: inputType})
	}
	return reflect.StructOf(structFields), nil
}

func getInputType(graphqlType ast.Type, hasDefault bool) (reflect.Type, error) {
	innerType, isNonNull := unwrapNonNull(graphqlType)
	var goType reflect.Type
	var err error
	switch typedType := innerType.(type) {
	case *ast.InputObject:
		goType, err = getInputValuesType(typedType.Values)
	case *ast.List:
		var elemType reflect.Type
		elemType, err = getInputType(typedType.OfType, false)
		goType = reflect.SliceOf(elemType)
	default:
		goType, err = getScalarType(innerType)
	}
	if err != nil {
		return nil, err
	}
	if !isNonNull && !hasDefault {
		return reflect.PointerTo(goType), nil
	}
	return goType, nil
}

func getScalarType(graphqlType ast.Type) (reflect.Type, error) {
	switch typedType := graphqlType.(type) {
	case *ast.EnumTypeDefinition:
		return reflect.TypeFor[string](), nil
	case *ast.ScalarTypeDefinition:
		if scalarType, isSupported := scalarTypes[typedType.Name]; isSupported {
			return scalarType, nil
		}
	}
	return nil, fmt.Errorf("type %s is not supported by the executor", graphqlType.String())
}

func getGoFieldName(graphqlName string) (string, error) {
	if graphqlName == "" || !(graphqlName[0] >= 'a' && graphqlName[0] <= 'z' || graphqlName[0] >= 'A' && graphqlName[0] <= 'Z') {
		return "", fmt.Errorf("argument %q must start with a letter", graphqlName)
	}
	return strings.ToUpper(graphqlName[:1]) + graphqlName[1:], nil
}

// getObjectValue *struct del tipo con sus func atadas a parent (el valor del padre).
func (b *resolverBuilder) getObjectValue(typeName string, parent map[string]any) reflect.Value {
	objectType := b.objectTypes[typeName]
	objectDefinition := b.astSchema.Types[typeName].(*ast.ObjectTypeDefinition)
	objectValue := reflect.New(objectType)
	for i, fieldDefinition := range objectDefinition.Fields {
		resolver := b.resolversByType[typeName][fieldDefinition.Name]
		funcType := objectType.Field(i).Type
		objectValue.Elem().Field(i).Set(reflect.MakeFunc(funcType, func(in []reflect.Value) []reflect.Value {
			return b.resolveField(fieldDefinition, resolver, parent, funcType.Out(0), in)
		}))
	}
	return objectValue
}

// resolveField el resolveField del decorador de task-php: el resolver del campo o, sin el, parent[campo].
func (b *resolverBuilder) resolveField(
	fieldDefinition *ast.FieldDefinition,
	resolver schema.GraphqlResolver,
	parent map[string]any,
	outType reflect.Type,
	in []reflect.Value,
) []reflect.Value {
	value := parent[fieldDefinition.Name]
	if resolver != nil {
		args := map[string]any{}
		if len(fieldDefinition.Arguments) > 0 {
			args = getArgsAsMap(fieldDefinition.Arguments, in[1])
		}
		result, err := resolver(getHttpRequester(in[0].Interface().(context.Context)), args, parent)
		if err == nil {
			value, err = getAsPrimitives(result)
		}
		if err != nil {
			return []reflect.Value{reflect.Zero(outType), reflect.ValueOf(&err).Elem()}
		}
	}
	return []reflect.Value{b.getOutputValue(fieldDefinition.Type, outType, value), reflect.Zero(errorType)}
}

// getAsPrimitives el ToArray del ResultDto como map/slice de primitivos (las claves son sus tags json).
func getAsPrimitives(result any) (any, error) {
	jsonResult, err := json.Marshal(result)
	if err != nil {
		return nil, err
	}
	jsonDecoder := json.NewDecoder(bytes.NewReader(jsonResult))
	jsonDecoder.UseNumber()
	var primitives any
	if err := jsonDecoder.Decode(&primitives); err != nil {
		return nil, err
	}
	return primitives, nil
}

// getOutputValue el valor primitivo convertido al tipo Go del campo. Un nulo en un campo no nulo lo rechaza
// graph-gophers (objeto) o sale como el valor cero (escalar).
func (b *resolverBuilder) getOutputValue(graphqlType ast.Type, goType reflect.Type, value any) reflect.Value {
	innerType, isNonNull := unwrapNonNull(graphqlType)
	if objectDefinition, isObject := innerType.(*ast.ObjectTypeDefinition); isObject {
		parent, isMap := value.(map[string]any)
		if !isMap {
			return reflect.Zero(goType)
		}
		return b.getObjectValue(objectDefinition.Name, parent)
	}
	if !isNonNull {
		if value == nil {
			return reflect.Zero(goType)
		}
		pointerValue := reflect.New(goType.Elem())
		pointerValue.Elem().Set(b.getOutputValue(&ast.NonNull{OfType: innerType}, goType.Elem(), value))
		return pointerValue
	}
	if listType, isList := innerType.(*ast.List); isList {
		items, _ := value.([]any)
		listValue := reflect.MakeSlice(goType, 0, len(items))
		for _, item := range items {
			listValue = reflect.Append(listValue, b.getOutputValue(listType.OfType, goType.Elem(), item))
		}
		return listValue
	}
	return getScalarValue(goType, value)
}

func getScalarValue(goType reflect.Type, value any) reflect.Value {
	switch goType.Kind() {
	case reflect.Int32:
		return reflect.ValueOf(int32(application.ToInt(getJsonNumber(value)))).Convert(goType)
	case reflect.Float64:
		floatValue, _ := json.Number(application.ToString(value)).Float64()
		return reflect.ValueOf(floatValue).Convert(goType)
	case reflect.Bool:
		boolValue, _ := value.(bool)
		return reflect.ValueOf(boolValue).Convert(goType)
	default:
		return reflect.ValueOf(application.ToString(value)).Convert(goType)
	}
}

func getJsonNumber(value any) any {
	if jsonNumber, isNumber := value.(json.Number); isNumber {
		if intValue, err := jsonNumber.Int64(); err == nil {
			return intValue
		}
		floatValue, _ := jsonNumber.Float64()
		return floatValue
	}
	return value
}

// getArgsAsMap el $args de task-php: {nombre del schema => valor}; un nulable que no llega no va.
func getArgsAsMap(inputValues ast.ArgumentsDefinition, argsValue reflect.Value) map[string]any {
	args := map[string]any{}
	for _, inputValue := range inputValues {
		goFieldName, _ := getGoFieldName(inputValue.Name.Name)
		if value := getInputAsPrimitive(inputValue.Type, argsValue.FieldByName(goFieldName)); value != nil {
			args[inputValue.Name.Name] = value
		}
	}
	return args
}

func getInputAsPrimitive(graphqlType ast.Type, inputValue reflect.Value) any {
	innerType, _ := unwrapNonNull(graphqlType)
	if inputValue.Kind() == reflect.Pointer {
		if inputValue.IsNil() {
			return nil
		}
		inputValue = inputValue.Elem()
	}
	switch typedType := innerType.(type) {
	case *ast.InputObject:
		return getArgsAsMap(typedType.Values, inputValue)
	case *ast.List:
		items := []any{}
		for i := range inputValue.Len() {
			items = append(items, getInputAsPrimitive(typedType.OfType, inputValue.Index(i)))
		}
		return items
	}
	switch inputValue.Kind() {
	case reflect.Int32:
		return int(inputValue.Int())
	case reflect.String:
		return inputValue.String()
	default:
		return inputValue.Interface()
	}
}

func unwrapNonNull(graphqlType ast.Type) (ast.Type, bool) {
	if nonNullType, isNonNull := graphqlType.(*ast.NonNull); isNonNull {
		return nonNullType.OfType, true
	}
	return graphqlType, false
}
