function initSelect2(container) {
    if (!window.jQuery || !$.fn.select2) return;

    $(container)
        .find("select")
        .each(function () {
            const $select = $(this);

            if ($select.hasClass("select2-hidden-accessible")) {
                $select.select2("destroy");
            }

            const dropdownParent =
                $select.data("dropdown-parent") ||
                $select.attr("data-dropdown-parent") ||
                "#cmsCreateStepperModal";

            $select.select2({
                dropdownParent: $(dropdownParent),
                width: "100%",
                placeholder:
                    $select.attr("data-placeholder") || "Select an option",
                allowClear: true,
            });
        });
}

document.addEventListener("DOMContentLoaded", function () {
    const stepperEl = document.querySelector("#cms_create_stepper");
    const form = document.querySelector("#cmsCreateStepperForm");

    if (!stepperEl || !form) return;

    const stepper = new KTStepper(stepperEl);

    function validateCurrentStep(stepIndex) {
        const contents = stepperEl.querySelectorAll(
            '[data-kt-stepper-element="content"]',
        );
        const currentContent = contents[stepIndex - 1];

        if (!currentContent) return true;

        const requiredFields = currentContent.querySelectorAll("[required]");
        let isValid = true;
        let firstInvalidField = null;

        requiredFields.forEach((field) => {
            const value = field.value ? field.value.trim() : "";

            if (!value) {
                isValid = false;
                field.classList.add("is-invalid");

                if (!firstInvalidField) {
                    firstInvalidField = field;
                }
            } else {
                field.classList.remove("is-invalid");
            }
        });

        if (!isValid) {
            Swal.fire({
                icon: "error",
                title: "Missing Required Fields",
                text: "Please complete all required fields before continuing.",
            });

            if (firstInvalidField) {
                firstInvalidField.focus();
            }
        }

        return isValid;
    }

    function toggleActionButtons() {
        const currentStep = stepper.getCurrentStepIndex();
        const totalSteps = stepperEl.querySelectorAll(
            '[data-kt-stepper-element="content"]',
        ).length;

        const nextBtn = stepperEl.querySelector(
            '[data-kt-stepper-action="next"]',
        );
        const submitBtn = stepperEl.querySelector(
            '[data-kt-stepper-action="submit"]',
        );

        if (currentStep === totalSteps) {
            nextBtn.classList.add("d-none");
            submitBtn.classList.remove("d-none");
        } else {
            nextBtn.classList.remove("d-none");
            submitBtn.classList.add("d-none");
        }
    }

    stepper.on("kt.stepper.next", function (stepperObj) {
        const currentStep = stepperObj.getCurrentStepIndex();

        if (!validateCurrentStep(currentStep)) {
            return;
        }

        stepperObj.goNext();
        toggleActionButtons();
    });

    stepper.on("kt.stepper.previous", function (stepperObj) {
        stepperObj.goPrevious();
        toggleActionButtons();
    });

    stepper.on("kt.stepper.submit", function (e) {
        e.preventDefault();

        const totalSteps = stepperEl.querySelectorAll(
            '[data-kt-stepper-element="content"]',
        ).length;

        if (!validateCurrentStep(totalSteps)) {
            return;
        }

        form.submit();
    });

    const modalEl = document.getElementById("cmsCreateStepperModal");
    if (modalEl) {
        modalEl.addEventListener("shown.bs.modal", function () {
            toggleActionButtons();
        });

        modalEl.addEventListener("hidden.bs.modal", function () {
            form.reset();

            form.querySelectorAll(".is-invalid").forEach((el) => {
                el.classList.remove("is-invalid");
            });

            while (stepper.getCurrentStepIndex() > 1) {
                stepper.goPrevious();
            }

            toggleActionButtons();

            $(modalEl)
                .find("select")
                .each(function () {
                    if ($(this).hasClass("select2-hidden-accessible")) {
                        $(this).val(null).trigger("change");
                    }
                });
        });
    }

    toggleActionButtons();
});

document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("cmsCreateStepperModal");
    if (!modal) return;

    function refreshRemoveButtons(repeater) {
        const rows = repeater.querySelectorAll(".cms-repeater-item");
        rows.forEach((row) => {
            const btn = row.querySelector(".cms-remove-repeater-row");
            if (!btn) return;

            if (rows.length === 1) {
                btn.setAttribute("disabled", "disabled");
                btn.classList.add("disabled");
            } else {
                btn.removeAttribute("disabled");
                btn.classList.remove("disabled");
            }
        });
    }

    function clearRowValues(row) {
        row.querySelectorAll("input, textarea, select").forEach((field) => {
            if (field.tagName === "SELECT") {
                field.selectedIndex = 0;
                if (
                    window.jQuery &&
                    $(field).hasClass("select2-hidden-accessible")
                ) {
                    $(field).val(null).trigger("change");
                }
            } else if (field.type === "checkbox" || field.type === "radio") {
                field.checked = false;
            } else {
                field.value = "";
            }

            field.classList.remove("is-invalid");
        });
    }

    modal.addEventListener("click", function (e) {
        const addBtn = e.target.closest(".cms-add-repeater-row");
        if (addBtn) {
            const repeater = addBtn.closest(".cms-repeater");
            const template = repeater.querySelector(".cms-repeater-template");
            const items = repeater.querySelector(".cms-repeater-items");

            if (!template || !items) return;

            const clone = document.importNode(template.content, true);
            items.appendChild(clone);

            const appendedRow = items.lastElementChild;
            if (appendedRow) {
                clearRowValues(appendedRow);
                initSelect2(appendedRow);
            }

            refreshRemoveButtons(repeater);
            return;
        }

        const removeBtn = e.target.closest(".cms-remove-repeater-row");
        if (removeBtn) {
            const repeater = removeBtn.closest(".cms-repeater");
            const row = removeBtn.closest(".cms-repeater-item");

            if (!repeater || !row) return;

            const rows = repeater.querySelectorAll(".cms-repeater-item");
            if (rows.length === 1) return;

            row.remove();
            refreshRemoveButtons(repeater);
        }
    });

    modal.addEventListener("shown.bs.modal", function () {
        modal.querySelectorAll(".cms-repeater").forEach(refreshRemoveButtons);
        initSelect2(modal);
    });

    modal.addEventListener("hidden.bs.modal", function () {
        modal.querySelectorAll(".cms-repeater").forEach((repeater) => {
            const items = repeater.querySelector(".cms-repeater-items");
            const rows = items.querySelectorAll(".cms-repeater-item");

            rows.forEach((row, index) => {
                if (index === 0) {
                    clearRowValues(row);
                } else {
                    row.remove();
                }
            });

            refreshRemoveButtons(repeater);
        });
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const modalEl = document.getElementById("cmsEditStepperModal");
    const form = document.getElementById("cmsEditStepperForm");
    const stepperEl = document.getElementById("cms_edit_stepper");

    if (!modalEl || !form || !stepperEl) return;

    const stepper = new KTStepper(stepperEl);

    function validateCurrentStep(stepIndex) {
        const contents = stepperEl.querySelectorAll(
            '[data-kt-stepper-element="content"]',
        );
        const currentContent = contents[stepIndex - 1];

        if (!currentContent) return true;

        const requiredFields = currentContent.querySelectorAll("[required]");
        let isValid = true;
        let firstInvalidField = null;

        requiredFields.forEach((field) => {
            const value = field.value ? field.value.trim() : "";

            if (!value) {
                isValid = false;
                field.classList.add("is-invalid");

                if (!firstInvalidField) {
                    firstInvalidField = field;
                }
            } else {
                field.classList.remove("is-invalid");
            }
        });

        if (!isValid) {
            Swal.fire({
                icon: "error",
                title: "Missing Required Fields",
                text: "Please complete all required fields before continuing.",
            });

            if (firstInvalidField) {
                firstInvalidField.focus();
            }
        }

        return isValid;
    }

    function toggleButtons() {
        const currentStep = stepper.getCurrentStepIndex();
        const totalSteps = stepperEl.querySelectorAll(
            '[data-kt-stepper-element="content"]',
        ).length;

        const nextBtn = stepperEl.querySelector(
            '[data-kt-stepper-action="next"]',
        );
        const submitBtn = stepperEl.querySelector(
            '[data-kt-stepper-action="submit"]',
        );

        if (currentStep === totalSteps) {
            nextBtn.classList.add("d-none");
            submitBtn.classList.remove("d-none");
        } else {
            nextBtn.classList.remove("d-none");
            submitBtn.classList.add("d-none");
        }
    }

    function clearRowValues(row) {
        row.querySelectorAll("input, textarea, select").forEach((field) => {
            if (field.tagName === "SELECT") {
                field.selectedIndex = 0;
                if (
                    window.jQuery &&
                    $(field).hasClass("select2-hidden-accessible")
                ) {
                    $(field).val(null).trigger("change");
                }
            } else {
                field.value = "";
            }
            field.classList.remove("is-invalid");
        });
    }

    function refreshRemoveButtons(repeater) {
        const rows = repeater.querySelectorAll(".cms-repeater-item");
        rows.forEach((row) => {
            const btn = row.querySelector(".cms-remove-repeater-row");
            if (!btn) return;

            if (rows.length === 1) {
                btn.setAttribute("disabled", "disabled");
                btn.classList.add("disabled");
            } else {
                btn.removeAttribute("disabled");
                btn.classList.remove("disabled");
            }
        });
    }

    function resetRepeater() {
        modalEl.querySelectorAll(".cms-repeater").forEach((repeater) => {
            const items = repeater.querySelector(".cms-repeater-items");
            const rows = items.querySelectorAll(".cms-repeater-item");

            rows.forEach((row, index) => {
                if (index === 0) {
                    clearRowValues(row);
                } else {
                    row.remove();
                }
            });

            refreshRemoveButtons(repeater);
        });
    }

    function populateRepeaterRow(row, values = {}) {
        row.querySelectorAll("input, textarea, select").forEach((field) => {
            const rawName = field.getAttribute("name") || "";
            const normalizedName = rawName.replace(/\[\]$/, "");

            if (Object.prototype.hasOwnProperty.call(values, normalizedName)) {
                if (field.tagName === "SELECT") {
                    field.value = values[normalizedName] ?? "";

                    if (
                        window.jQuery &&
                        $(field).hasClass("select2-hidden-accessible")
                    ) {
                        $(field)
                            .val(values[normalizedName] ?? "")
                            .trigger("change");
                    }
                } else if (field.type === "checkbox") {
                    field.checked = Boolean(values[normalizedName]);
                } else if (field.type === "radio") {
                    field.checked = field.value == values[normalizedName];
                } else {
                    field.value = values[normalizedName] ?? "";
                }
            }
        });
    }

    function addRepeaterRow(repeater, values = {}) {
        const template = repeater.querySelector(".cms-repeater-template");
        const items = repeater.querySelector(".cms-repeater-items");

        let row;
        const existingRows = items.querySelectorAll(".cms-repeater-item");

        if (existingRows.length === 1) {
            row = existingRows[0];

            const hasValue = Array.from(
                row.querySelectorAll("input, textarea, select"),
            ).some((field) => {
                if (field.type === "checkbox" || field.type === "radio") {
                    return field.checked;
                }
                return field.value && field.value.trim() !== "";
            });

            if (hasValue) {
                const clone = document.importNode(template.content, true);
                items.appendChild(clone);
                row = items.lastElementChild;
            }
        } else {
            const clone = document.importNode(template.content, true);
            items.appendChild(clone);
            row = items.lastElementChild;
        }

        clearRowValues(row);
        populateRepeaterRow(row, values);
        initSelect2(row);
        refreshRemoveButtons(repeater);
    }

    stepper.on("kt.stepper.next", function (stepperObj) {
        const currentStep = stepperObj.getCurrentStepIndex();

        if (!validateCurrentStep(currentStep)) return;

        stepperObj.goNext();
        toggleButtons();
    });

    stepper.on("kt.stepper.previous", function (stepperObj) {
        stepperObj.goPrevious();
        toggleButtons();
    });

    stepper.on("kt.stepper.submit", function (e) {
        e.preventDefault();

        const totalSteps = stepperEl.querySelectorAll(
            '[data-kt-stepper-element="content"]',
        ).length;

        if (!validateCurrentStep(totalSteps)) return;

        form.submit();
    });

    document.addEventListener("click", function (e) {
        const btn = e.target.closest(".btn-cms-edit-stepper");
        if (!btn) return;

        form.setAttribute("action", btn.dataset.updateUrl || "#");

        const nameInput = modalEl.querySelector("#edit_name");
        const locationInput = modalEl.querySelector("#edit_location");
        const remarksInput = modalEl.querySelector("#edit_remarks");

        if (nameInput) nameInput.value = btn.dataset.name || "";
        if (locationInput) locationInput.value = btn.dataset.location || "";
        if (remarksInput) remarksInput.value = btn.dataset.remarks || "";

        resetRepeater();

        const repeater = modalEl.querySelector(".cms-repeater");
        let repeaterItems = [];

        try {
            repeaterItems = JSON.parse(btn.dataset.repeaterItems || "[]");
        } catch (error) {
            repeaterItems = [];
        }

        if (repeater) {
            const firstRow = repeater.querySelector(".cms-repeater-item");
            clearRowValues(firstRow);

            if (repeaterItems.length > 0) {
                populateRepeaterRow(firstRow, repeaterItems[0]);

                for (let i = 1; i < repeaterItems.length; i++) {
                    addRepeaterRow(repeater, repeaterItems[i]);
                }
            }
        }

        while (stepper.getCurrentStepIndex() > 1) {
            stepper.goPrevious();
        }

        toggleButtons();
    });

    modalEl.addEventListener("click", function (e) {
        const addBtn = e.target.closest(".cms-add-repeater-row");
        if (addBtn) {
            const repeater = addBtn.closest(".cms-repeater");
            addRepeaterRow(repeater);
            return;
        }

        const removeBtn = e.target.closest(".cms-remove-repeater-row");
        if (removeBtn) {
            const repeater = removeBtn.closest(".cms-repeater");
            const row = removeBtn.closest(".cms-repeater-item");

            if (!repeater || !row) return;

            const rows = repeater.querySelectorAll(".cms-repeater-item");
            if (rows.length === 1) return;

            row.remove();
            refreshRemoveButtons(repeater);
        }
    });

    modalEl.addEventListener("shown.bs.modal", function () {
        toggleButtons();
        modalEl.querySelectorAll(".cms-repeater").forEach(refreshRemoveButtons);
        initSelect2(modalEl);
    });

    modalEl.addEventListener("hidden.bs.modal", function () {
        form.reset();

        form.querySelectorAll(".is-invalid").forEach((el) => {
            el.classList.remove("is-invalid");
        });

        resetRepeater();

        while (stepper.getCurrentStepIndex() > 1) {
            stepper.goPrevious();
        }

        toggleButtons();

        $(modalEl)
            .find("select")
            .each(function () {
                if ($(this).hasClass("select2-hidden-accessible")) {
                    $(this).val(null).trigger("change");
                }
            });
    });

    toggleButtons();
});
