package server

import (
	"net"
	"os"
)

// ServerIdentifier nombre e ip de la maquina que ejecuta la app: en un contenedor, su hostname y su ip en la red de
// docker.
type ServerIdentifier struct{}

func GetInstance() *ServerIdentifier {
	return &ServerIdentifier{}
}

func (s *ServerIdentifier) GetServerName() string {
	hostname, err := os.Hostname()
	if err != nil {
		return ""
	}
	return hostname
}

// GetServerIp como gethostbyname(): la primera IPv4 del hostname, o el propio nombre si no se resuelve.
func (s *ServerIdentifier) GetServerIp() string {
	serverName := s.GetServerName()
	addresses, err := net.LookupHost(serverName)
	if err != nil {
		return serverName
	}
	for _, address := range addresses {
		if ip := net.ParseIP(address); ip != nil && ip.To4() != nil {
			return address
		}
	}
	return serverName
}
