package main

import (
	"net/http"
	"os"
	"path/filepath"
	"strings"

	"task-go/src/core/boot"
)

// PublicFilesServer lo que en task-php hace nginx: si la url es un fichero de public/ se sirve tal cual; si no, pasa
// al front controller (try_files $uri @rewriteapp). Los ficheros ocultos (.env, .git...) nunca: 404.
type PublicFilesServer struct {
	indexMain http.Handler
}

func PublicFilesServerGetInstance(indexMain http.Handler) *PublicFilesServer {
	return &PublicFilesServer{indexMain: indexMain}
}

func (s *PublicFilesServer) ServeHTTP(responseWriter http.ResponseWriter, request *http.Request) {
	if strings.Contains(request.URL.Path, "/.") {
		http.NotFound(responseWriter, request)
		return
	}

	if pathFile := s.getPathPublicFile(request.URL.Path); pathFile != "" {
		http.ServeFile(responseWriter, request, pathFile)
		return
	}
	s.indexMain.ServeHTTP(responseWriter, request)
}

// getPathPublicFile la ruta del fichero dentro de public/, o "" si no existe o no es un fichero.
func (s *PublicFilesServer) getPathPublicFile(urlPath string) string {
	relativePath := filepath.FromSlash(filepath.Clean("/" + urlPath))
	pathFile := filepath.Join(boot.BootPathsEnum.PATH_PUBLIC, relativePath)
	if !strings.HasPrefix(pathFile, boot.BootPathsEnum.PATH_PUBLIC+string(filepath.Separator)) {
		return ""
	}
	fileInfo, err := os.Stat(pathFile)
	if err != nil || fileInfo.IsDir() {
		return ""
	}
	return pathFile
}
