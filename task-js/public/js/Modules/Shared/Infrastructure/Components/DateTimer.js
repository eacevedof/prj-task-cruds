const MS_PER_DAY = 24 * 60 * 60 * 1000;

/**
 * Fechas de calendario en la zona horaria del navegador, en formato YYYY-MM-DD (el de los input date).
 */
export class DateTimer {

    /**
     * @returns {DateTimer}
     */
    static getInstance() {
        return new DateTimer();
    }

    /**
     * @param {number} days dias a sumar a hoy (negativo: hacia atras)
     * @returns {string} YYYY-MM-DD local, no UTC: a las 23:30 en Madrid "mañana" es mañana en Madrid
     */
    getDateFromToday(days) {
        const date = new Date(Date.now() + days * MS_PER_DAY);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        return `${year}-${month}-${day}`;
    }
}
