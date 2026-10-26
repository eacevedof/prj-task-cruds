use std::env;
use std::fs;
use std::net::{IpAddr, ToSocketAddrs};

/// nombre e ip de la maquina que ejecuta la app: en un contenedor, su hostname y su ip en la red de docker.
pub struct ServerIdentifier;

impl ServerIdentifier {
    pub fn get_instance() -> Self {
        Self
    }

    /// el hostname del kernel (en el contenedor, su id) o, fuera de linux, el de las variables del sistema.
    pub fn get_server_name(&self) -> String {
        if let Ok(hostname) = fs::read_to_string("/proc/sys/kernel/hostname") {
            return hostname.trim().to_string();
        }
        env::var("HOSTNAME").or_else(|_| env::var("COMPUTERNAME")).unwrap_or_default()
    }

    /// como gethostbyname(): la primera IPv4 del hostname, o el propio nombre si no se resuelve.
    pub fn get_server_ip(&self) -> String {
        let server_name = self.get_server_name();
        let Ok(socket_addresses) = (server_name.as_str(), 0).to_socket_addrs() else {
            return server_name;
        };
        socket_addresses
            .map(|socket_address| socket_address.ip())
            .find(IpAddr::is_ipv4)
            .map(|ip| ip.to_string())
            .unwrap_or(server_name)
    }
}
