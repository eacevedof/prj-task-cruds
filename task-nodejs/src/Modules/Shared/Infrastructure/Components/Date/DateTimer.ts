/**
 * Fechas en texto "Y-m-d H:i:s" (el formato de la BD) entre zonas horarias. Los formatos son los de date() de PHP
 * con las letras que se usan (Y, m, d, H, i, s).
 * Sin las de zona por ip de task-php (ip-api.com): alli no las usa nadie.
 */
export class DateTimer {

    static readonly TZ_UTC = "UTC";
    static readonly FORMAT_FULL_DT = "Y-m-d H:i:s";
    static readonly FORMAT_ONLY_DATE = "Y-m-d";

    static getInstance(): DateTimer {
        return new DateTimer();
    }

    /**
     * de UTC a TZx: "2022-01-03 10:11:22" -> la misma hora en targetTz.
     * @throws RangeError si la zona no existe
     */
    getUtcDtInTargetTz(
        utcDt: string,
        targetTz: string = DateTimer.TZ_UTC,
        format: string = DateTimer.FORMAT_FULL_DT
    ): string {
        return this.getSourceDtIntoTargetTz(utcDt, DateTimer.TZ_UTC, targetTz, format);
    }

    /**
     * de TZx a TZy. Por defecto va de UI => BD (de UTCx a UTC0).
     * @throws RangeError si una zona no existe o la fecha no tiene el formato "Y-m-d H:i:s"
     */
    getSourceDtIntoTargetTz(
        sourceDt: string,
        sourceTz: string,
        targetTz: string = DateTimer.TZ_UTC,
        format: string = DateTimer.FORMAT_FULL_DT
    ): string {
        const matches = /^(\d{4})-(\d{2})-(\d{2})(?: (\d{2}):(\d{2}):(\d{2}))?$/.exec(sourceDt);
        if (!matches) {
            throw new RangeError(`DateTimer: wrong date ${sourceDt}`);
        }
        const [, year, month, day, hours = "0", minutes = "0", seconds = "0"] = matches;
        const sourceDtAsUtc = Date.UTC(+year, +month - 1, +day, +hours, +minutes, +seconds);
        // el instante cuya hora local en sourceTz es sourceDt (la diferencia con UTC de ese momento)
        const utcTime = sourceDtAsUtc - this.#getOffsetMs(sourceDtAsUtc, sourceTz);
        return this.#getFormatted(new Date(utcTime), targetTz, format);
    }

    /**
     * fecha y hora actuales en TZx (por defecto UTC)
     */
    getNowDtIntoTargetTz(targetTz: string = DateTimer.TZ_UTC, format: string = DateTimer.FORMAT_FULL_DT): string {
        return this.#getFormatted(new Date(), targetTz, format);
    }

    #getOffsetMs(utcTime: number, timeZone: string): number {
        const dateParts = this.#getDateParts(new Date(utcTime), timeZone);
        const localTimeAsUtc = Date.UTC(
            +dateParts.Y, +dateParts.m - 1, +dateParts.d, +dateParts.H, +dateParts.i, +dateParts.s
        );
        return localTimeAsUtc - utcTime;
    }

    #getFormatted(date: Date, timeZone: string, format: string): string {
        const dateParts = this.#getDateParts(date, timeZone);
        return format.replace(/[YmdHis]/g, (letter) => dateParts[letter]);
    }

    /**
     * Las partes de la fecha en timeZone con la letra de date() que las pinta: {Y: "2026", m: "01", ...}.
     */
    #getDateParts(date: Date, timeZone: string): Record<string, string> {
        const partLetters: Record<string, string> = {
            year: "Y", month: "m", day: "d", hour: "H", minute: "i", second: "s",
        };
        const dateTimeFormat = new Intl.DateTimeFormat("en-CA", {
            timeZone,
            hourCycle: "h23",
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
        });

        const dateParts: Record<string, string> = {};
        for (const datePart of dateTimeFormat.formatToParts(date)) {
            const letter = partLetters[datePart.type];
            if (letter) dateParts[letter] = datePart.value;
        }
        return dateParts;
    }
}
