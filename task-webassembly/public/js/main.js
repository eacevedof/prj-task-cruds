/**
 * Loader de la SPA: descarga main.wasm (toda la aplicacion, en Go) y lo arranca con el runtime de Go (wasm_exec.js).
 * Es el unico JS propio: vistas, controllers, casos de uso y repositorios viven en el wasm (cmd/wasm/main.go).
 * Si el wasm no llega a arrancar, el aviso de error fatal de la propia pagina.
 */
const WASM_PATH = "/wasm/main.wasm";

const go = new Go();
WebAssembly.instantiateStreaming(fetch(WASM_PATH), go.importObject)
    .then((result) => go.run(result.instance))
    .catch((error) => {
        console.error("main.wasm", error);
        document.getElementById("div-fatal-error").classList.remove("hidden");
    });
