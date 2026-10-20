// Boton "Copiar" en los bloques de codigo con un curl (el resto: respuestas de ejemplo, no se copian).
(function () {
    const CURL_PATTERN = /^\s*curl\s/m;
    const LABEL_COPY = "Copiar";
    const LABEL_COPIED = "Copiado";
    const LABEL_ERROR = "Error";
    const LABEL_RESET_MS = 1500;

    // navigator.clipboard solo existe en contexto seguro (https o localhost): fuera de el, el metodo clasico
    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            const textarea = document.createElement("textarea");
            textarea.value = text;
            textarea.setAttribute("readonly", "");
            textarea.style.position = "fixed";
            textarea.style.opacity = "0";
            document.body.appendChild(textarea);
            textarea.select();
            const isCopied = document.execCommand("copy");
            document.body.removeChild(textarea);
            isCopied ? resolve() : reject();
        });
    }

    function showLabel(button, label) {
        button.textContent = label;
        setTimeout(function () { button.textContent = LABEL_COPY; }, LABEL_RESET_MS);
    }

    document.querySelectorAll("pre > code").forEach(function (code) {
        if (!CURL_PATTERN.test(code.textContent)) return;

        const button = document.createElement("button");
        button.type = "button";
        button.className = "copy-button";
        button.textContent = LABEL_COPY;
        button.addEventListener("click", function () {
            copyText(code.textContent.trim())
                .then(function () { showLabel(button, LABEL_COPIED); })
                .catch(function () { showLabel(button, LABEL_ERROR); });
        });
        code.parentElement.classList.add("has-copy-button");
        code.parentElement.appendChild(button);
    });
})();
