// API de task-go: el public/index.php de task-php. go run ./cmd/api -addr=:8080
package main

import (
	"flag"
	"log"
	"net/http"
	"time"

	"task-go/src/core/config"
	"task-go/src/core/routes"
)

func main() {
	listenAddress := flag.String("addr", ":8080", "direccion de escucha")
	flag.Parse()

	if err := config.AppbootLoadenv(); err != nil {
		log.Fatal(err)
	}

	server := &http.Server{
		Addr:              *listenAddress,
		Handler:           PublicFilesServerGetInstance(IndexMainGetInstance(routes.GetRoutes())),
		ReadHeaderTimeout: 10 * time.Second,
	}
	log.Printf("task-go api listening on %s", *listenAddress)
	log.Fatal(server.ListenAndServe())
}
