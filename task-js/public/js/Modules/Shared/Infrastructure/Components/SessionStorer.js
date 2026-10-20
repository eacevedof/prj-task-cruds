const SESSION_KEY = "task-js-session";

/**
 * Sesion del usuario en sessionStorage (se borra al cerrar la pestaña). Como todo componente no captura:
 * si el navegador bloquea sessionStorage el error sube al controller.
 */
export class SessionStorer {

    /**
     * @returns {SessionStorer}
     */
    static getInstance() {
        return new SessionStorer();
    }

    /**
     * @returns {{apiToken: string, userUuid: string, userName: string, userEmail: string}|null}
     */
    getSession() {
        const session = JSON.parse(sessionStorage.getItem(SESSION_KEY) ?? "null");
        return session?.apiToken ? session : null;
    }

    /**
     * @returns {string}
     */
    getApiToken() {
        return this.getSession()?.apiToken ?? "";
    }

    /**
     * @returns {string}
     */
    getUserUuid() {
        return this.getSession()?.userUuid ?? "";
    }

    /**
     * @param {{apiToken: string, userUuid: string, userName: string, userEmail: string}} session
     */
    saveSession(session) {
        sessionStorage.setItem(SESSION_KEY, JSON.stringify(session));
    }

    removeSession() {
        sessionStorage.removeItem(SESSION_KEY);
    }
}
