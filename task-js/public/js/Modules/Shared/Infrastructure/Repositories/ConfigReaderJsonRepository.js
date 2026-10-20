const CONFIG_PATH = "/config.json";

/**
 * Configuracion de la SPA: la sirve nginx desde las variables del contenedor (docker/.env).
 */
export class ConfigReaderJsonRepository {

    /**
     * @returns {ConfigReaderJsonRepository}
     */
    static getInstance() {
        return new ConfigReaderJsonRepository();
    }

    /**
     * @returns {Promise<{apiBaseUrl: string}>}
     */
    async getConfig() {
        const response = await fetch(CONFIG_PATH, { headers: { "Accept": "application/json" } });
        return response.json();
    }
}
