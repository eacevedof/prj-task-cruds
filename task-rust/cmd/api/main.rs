//! API de task-rust: el public/index.php de task-php. cargo run --bin api -- --addr=0.0.0.0:8080

use std::env;
use std::io::Read;
use std::process;
use std::sync::Arc;
use std::thread;

use tiny_http::{Request, Server};

use task_rust::core::boot::index_main::IndexMain;
use task_rust::core::boot::public_files_server::PublicFilesServer;
use task_rust::core::config::appboot_loadenv;
use task_rust::core::routes::routes::get_routes;
use task_rust::modules::shared::infrastructure::components::http_requester::http_requester::HttpRequest;

const DEFAULT_LISTEN_ADDRESS: &str = "0.0.0.0:8080";
/// peticiones atendidas a la vez (un hilo por peticion en curso)
const WORKERS: usize = 8;
/// el body mas grande que se lee (el post_max_size de PHP)
const MAX_BODY_BYTES: u64 = 8 * 1024 * 1024;

fn main() {
    let listen_address = get_listen_address();
    if let Err(error) = appboot_loadenv() {
        eprintln!("{error}");
        process::exit(1);
    }

    let server = match Server::http(&listen_address) {
        Ok(server) => Arc::new(server),
        Err(error) => {
            eprintln!("task-rust api: cannot listen on {listen_address}: {error}");
            process::exit(1);
        }
    };
    let index_main = Arc::new(IndexMain::get_instance(get_routes()));
    println!("task-rust api listening on {listen_address}");

    let workers: Vec<_> = (0..WORKERS)
        .map(|_| {
            let (server, index_main) = (Arc::clone(&server), Arc::clone(&index_main));
            thread::spawn(move || {
                while let Ok(request) = server.recv() {
                    serve_request(&index_main, request);
                }
            })
        })
        .collect();
    for worker in workers {
        let _ = worker.join();
    }
}

/// --addr=host:puerto (por defecto 0.0.0.0:8080)
fn get_listen_address() -> String {
    env::args()
        .find_map(|argument| argument.strip_prefix("--addr=").map(str::to_string))
        .unwrap_or_else(|| DEFAULT_LISTEN_ADDRESS.to_string())
}

/// los ficheros de public/ los sirve PublicFilesServer (lo que hace nginx en task-php); el resto, IndexMain.
fn serve_request(index_main: &IndexMain, request: Request) {
    let Some(mut request) = PublicFilesServer::get_instance().get_request_if_not_public_file(request) else {
        return;
    };
    let http_request = get_http_request(&mut request);
    index_main.get_http_responder(http_request).echo_headers_and_body(request);
}

/// la peticion de tiny_http como HttpRequest: lo que en PHP son las superglobales.
fn get_http_request(request: &mut Request) -> HttpRequest {
    let mut body = vec![];
    let _ = request.as_reader().take(MAX_BODY_BYTES).read_to_end(&mut body);
    HttpRequest {
        method: request.method().as_str().to_string(),
        request_uri: request.url().to_string(),
        headers: request
            .headers()
            .iter()
            .map(|header| (header.field.as_str().as_str().to_string(), header.value.as_str().to_string()))
            .collect(),
        body,
        remote_addr: request.remote_addr().map(|remote_addr| remote_addr.ip().to_string()).unwrap_or_default(),
    }
}
