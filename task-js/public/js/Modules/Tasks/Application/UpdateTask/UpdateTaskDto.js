/**
 * Entrada del caso de uso: solo primitivos, todo string con trim.
 */
export class UpdateTaskDto {

    #apiToken;
    #taskUuid;
    #taskTitle;
    #taskStatus;
    #taskType;
    #targetDate;
    #taskDescription;

    /**
     * @param {Object} primitives
     */
    constructor(primitives) {
        this.#apiToken = String(primitives.apiToken ?? "").trim();
        this.#taskUuid = String(primitives.taskUuid ?? "").trim();
        this.#taskTitle = String(primitives.taskTitle ?? "").trim();
        this.#taskStatus = String(primitives.taskStatus ?? "").trim();
        this.#taskType = String(primitives.taskType ?? "").trim();
        this.#targetDate = String(primitives.targetDate ?? "").trim();
        this.#taskDescription = String(primitives.taskDescription ?? "").trim();
    }

    /**
     * @param {Object} primitives
     * @returns {UpdateTaskDto}
     */
    static fromPrimitives(primitives = {}) {
        return new UpdateTaskDto(primitives);
    }

    /**
     * @returns {string}
     */
    getApiToken() {
        return this.#apiToken;
    }

    /**
     * @returns {string}
     */
    getTaskUuid() {
        return this.#taskUuid;
    }

    /**
     * @returns {string}
     */
    getTaskTitle() {
        return this.#taskTitle;
    }

    /**
     * @returns {string}
     */
    getTaskStatus() {
        return this.#taskStatus;
    }

    /**
     * @returns {string}
     */
    getTaskType() {
        return this.#taskType;
    }

    /**
     * @returns {string}
     */
    getTargetDate() {
        return this.#targetDate;
    }

    /**
     * @returns {string}
     */
    getTaskDescription() {
        return this.#taskDescription;
    }
}
