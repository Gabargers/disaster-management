function initTooltips() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        if (!el._tooltipInstance) {
            el._tooltipInstance = new bootstrap.Tooltip(el);
        }
    });
}

document.addEventListener("DOMContentLoaded", initTooltips);

$(document).on("draw.dt", function () {
    initTooltips();
});
