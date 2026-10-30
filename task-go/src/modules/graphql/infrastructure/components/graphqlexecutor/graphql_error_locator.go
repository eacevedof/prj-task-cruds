package graphqlexecutor

import (
	"strings"
	"unicode/utf8"

	"github.com/graph-gophers/graphql-go/errors"
)

// graphqlErrorLocator da la posicion (linea y columna) del campo de la consulta al que apunta el path de un error.
// graph-gophers pone path en el error de un resolver pero no locations, y su parser de consultas es interno: este
// lector minimo de la consulta lo suple para que el error salga como en webonyx ({message, locations, path,
// extensions}). Solo se usa si algun error lo necesita; si la consulta no se entiende, el error se queda sin locations.
type graphqlErrorLocator struct {
	tokens    []locatorToken
	position  int
	fragments map[string][]locatorSelection
}

type locatorToken struct {
	value    string
	isString bool
	location errors.Location
}

// locatorSelection un campo (responseKey = alias o nombre), un fragmento con nombre (fragmentName) o uno inline
// (solo children).
type locatorSelection struct {
	responseKey  string
	fragmentName string
	location     errors.Location
	children     []locatorSelection
}

// fillMissingLocations pone locations a los errores que traen path y no las traen.
func fillMissingLocations(queryErrors []*errors.QueryError, query string, operationName string) {
	var locator *graphqlErrorLocator
	var operation []locatorSelection
	for _, queryError := range queryErrors {
		if len(queryError.Locations) > 0 || len(queryError.Path) == 0 {
			continue
		}
		if locator == nil {
			locator = newGraphqlErrorLocator(query)
			operation = locator.getOperation(operationName)
		}
		if location, found := locator.getLocation(operation, queryError.Path); found {
			queryError.Locations = []errors.Location{location}
		}
	}
}

func newGraphqlErrorLocator(query string) *graphqlErrorLocator {
	return &graphqlErrorLocator{tokens: getTokens(query), fragments: map[string][]locatorSelection{}}
}

// getOperation la operacion que se ejecuta (la de operationName o la primera) y, de paso, guarda los fragmentos.
func (l *graphqlErrorLocator) getOperation(operationName string) []locatorSelection {
	var firstOperation, namedOperation []locatorSelection
	for l.position < len(l.tokens) {
		token := l.next()
		switch {
		case token.value == "{":
			l.position--
			if selections := l.readSelectionSet(); firstOperation == nil {
				firstOperation = selections
			}
		case token.value == "fragment":
			fragmentName := l.next().value
			l.next() // on
			l.next() // tipo
			l.skipDirectives()
			l.fragments[fragmentName] = l.readSelectionSet()
		case token.value == "query" || token.value == "mutation" || token.value == "subscription":
			name := ""
			if l.peek().value != "(" && l.peek().value != "{" && l.peek().value != "@" {
				name = l.next().value
			}
			l.skipBalanced("(", ")")
			l.skipDirectives()
			selections := l.readSelectionSet()
			if firstOperation == nil {
				firstOperation = selections
			}
			if operationName != "" && name == operationName {
				namedOperation = selections
			}
		default:
			return firstOperation
		}
	}
	if namedOperation != nil {
		return namedOperation
	}
	return firstOperation
}

func (l *graphqlErrorLocator) readSelectionSet() []locatorSelection {
	selections := []locatorSelection{}
	if l.peek().value != "{" {
		return selections
	}
	l.next()
	for l.position < len(l.tokens) && l.peek().value != "}" {
		token := l.next()
		if token.value == "..." {
			if l.peek().value == "on" || l.peek().value == "@" || l.peek().value == "{" {
				if l.peek().value == "on" {
					l.next()
					l.next()
				}
				l.skipDirectives()
				selections = append(selections, locatorSelection{children: l.readSelectionSet()})
				continue
			}
			selections = append(selections, locatorSelection{fragmentName: l.next().value})
			l.skipDirectives()
			continue
		}
		field := locatorSelection{responseKey: token.value, location: token.location}
		if l.peek().value == ":" {
			l.next()
			l.next()
		}
		l.skipBalanced("(", ")")
		l.skipDirectives()
		field.children = l.readSelectionSet()
		selections = append(selections, field)
	}
	l.next()
	return selections
}

// getLocation baja por el path (los indices de lista no son campos y se saltan) hasta el ultimo campo.
func (l *graphqlErrorLocator) getLocation(selections []locatorSelection, path []any) (errors.Location, bool) {
	var field *locatorSelection
	for _, pathKey := range path {
		responseKey, isFieldKey := pathKey.(string)
		if !isFieldKey {
			continue
		}
		if field = l.findField(selections, responseKey, map[string]bool{}); field == nil {
			return errors.Location{}, false
		}
		selections = field.children
	}
	if field == nil {
		return errors.Location{}, false
	}
	return field.location, true
}

func (l *graphqlErrorLocator) findField(selections []locatorSelection, responseKey string, visitedFragments map[string]bool) *locatorSelection {
	for i := range selections {
		selection := &selections[i]
		switch {
		case selection.responseKey == responseKey:
			return selection
		case selection.fragmentName != "" && !visitedFragments[selection.fragmentName]:
			visitedFragments[selection.fragmentName] = true
			if field := l.findField(l.fragments[selection.fragmentName], responseKey, visitedFragments); field != nil {
				return field
			}
		case selection.responseKey == "" && selection.fragmentName == "":
			if field := l.findField(selection.children, responseKey, visitedFragments); field != nil {
				return field
			}
		}
	}
	return nil
}

func (l *graphqlErrorLocator) skipDirectives() {
	for l.peek().value == "@" {
		l.next()
		l.next()
		l.skipBalanced("(", ")")
	}
}

func (l *graphqlErrorLocator) skipBalanced(open string, close string) {
	if l.peek().value != open {
		return
	}
	depth := 0
	for l.position < len(l.tokens) {
		token := l.next()
		if token.isString {
			continue
		}
		if token.value == open {
			depth++
		}
		if token.value == close {
			if depth--; depth == 0 {
				return
			}
		}
	}
}

func (l *graphqlErrorLocator) peek() locatorToken {
	if l.position >= len(l.tokens) {
		return locatorToken{}
	}
	return l.tokens[l.position]
}

func (l *graphqlErrorLocator) next() locatorToken {
	token := l.peek()
	l.position++
	return token
}

// getTokens lexer minimo de GraphQL: nombres, numeros, strings (tambien """bloque""") y puntuacion, cada uno con su
// linea y columna (1-based, en caracteres); se saltan espacios, comas y comentarios.
func getTokens(query string) []locatorToken {
	tokens := []locatorToken{}
	line, lineStart := 1, 0
	getLocation := func(offset int) errors.Location {
		return errors.Location{Line: line, Column: utf8.RuneCountInString(query[lineStart:offset]) + 1}
	}
	newLine := func(offset int) {
		line++
		lineStart = offset
	}
	for i := 0; i < len(query); {
		char := query[i]
		switch {
		case char == '\n':
			i++
			newLine(i)
		case char == '\r':
			i++
			if i < len(query) && query[i] == '\n' {
				i++
			}
			newLine(i)
		case char == ' ' || char == '\t' || char == ',' || char == 0xEF:
			i++
			if char == 0xEF {
				i += 2 // BOM
			}
		case char == '#':
			for i < len(query) && query[i] != '\n' && query[i] != '\r' {
				i++
			}
		case strings.HasPrefix(query[i:], `"""`):
			location := getLocation(i)
			i += 3
			for i < len(query) && !strings.HasPrefix(query[i:], `"""`) {
				switch {
				case strings.HasPrefix(query[i:], `\"""`):
					i += 4
				case query[i] == '\n':
					i++
					newLine(i)
				default:
					i++
				}
			}
			i += 3
			tokens = append(tokens, locatorToken{isString: true, location: location})
		case char == '"':
			location := getLocation(i)
			for i++; i < len(query) && query[i] != '"' && query[i] != '\n'; i++ {
				if query[i] == '\\' {
					i++
				}
			}
			i++
			tokens = append(tokens, locatorToken{isString: true, location: location})
		case strings.HasPrefix(query[i:], "..."):
			tokens = append(tokens, locatorToken{value: "...", location: getLocation(i)})
			i += 3
		case isNameChar(char) || char == '-':
			start := i
			for i++; i < len(query) && (isNameChar(query[i]) || query[i] == '.' || query[i] == '+' || query[i] == '-'); i++ {
			}
			tokens = append(tokens, locatorToken{value: query[start:i], location: getLocation(start)})
		default:
			tokens = append(tokens, locatorToken{value: string(char), location: getLocation(i)})
			i++
		}
	}
	return tokens
}

func isNameChar(char byte) bool {
	return char == '_' || (char >= 'a' && char <= 'z') || (char >= 'A' && char <= 'Z') || (char >= '0' && char <= '9')
}
