(function () {
    function getCMS() {
        return window && window.CMS ? window.CMS : {};
    }

    function getFields() {
        var cms = getCMS();
        return Array.isArray(cms.fields) ? cms.fields : [];
    }

    function initSelect2(modalEl) {
        if (!modalEl) return;

        $(modalEl)
            .find('select[data-control="select2"]')
            .each(function () {
                var $s = $(this);
                if ($s.hasClass("select2-hidden-accessible")) return;

                $s.select2({
                    dropdownParent: $(modalEl),
                    placeholder: $s.data("placeholder") || "Select",
                    width: "100%",
                });
            });
    }

    function openModal(id) {
        var el = document.getElementById(id);
        if (!el) return null;

        var modal = bootstrap.Modal.getOrCreateInstance(el);
        modal.show();
        return el;
    }

    function toCamel(s) {
        return String(s).replace(/_([a-z])/g, function (_, c) {
            return c.toUpperCase();
        });
    }

    function fillEditFields(data, container) {
        var fields = getFields();
        var scope = container || document;

        fields.forEach(function (f) {
            var name = f.name;

            var el =
                scope.querySelector("#edit_" + CSS.escape(name)) ||
                scope.querySelector("#" + CSS.escape(name)) ||
                scope.querySelector('[name="' + name + '"]');

            if (!el) {
                console.log("No element found for:", name);
                return;
            }

            if (el.type === "file") {
                return;
            }

            var val =
                typeof data[name] !== "undefined"
                    ? data[name]
                    : typeof data[toCamel(name)] !== "undefined"
                      ? data[toCamel(name)]
                      : "";

            console.log("Filling:", name, "=>", val, el);

            if (el.tagName === "SELECT") {
                $(el).val(val).trigger("change");
            } else {
                el.value = val ?? "";
            }
        });
    }

    // EDIT
    $(document).on("click", ".btn-cms-edit", function () {
        var data = $(this).data();
        console.log("edit data:", data);
        console.log("fields:", getFields());

        $("#cmsEditForm").attr("action", data.updateUrl || "#");

        var modalEl = openModal("cmsEditModal");
        if (!modalEl) return;

        modalEl.addEventListener(
            "shown.bs.modal",
            function onShown() {
                modalEl.removeEventListener("shown.bs.modal", onShown);

                initSelect2(modalEl);
                fillEditFields(data, modalEl);
            },
            { once: true },
        );
    });

    // DELETE
    $(document).on("click", ".btn-cms-delete", function () {
        var data = $(this).data();

        $("#cmsDeleteForm").attr("action", data.deleteUrl || "#");
        $("#delete_name").text(data.name || "this item");

        var fields = getFields();
        var details = [];

        fields.forEach(function (f) {
            var name = f.name;
            if (name === "name") return;

            if (typeof data[name] === "undefined") return;

            var val = data[name];

            if (f.type === "select" && f.options) {
                details.push([f.label || name, f.options[val] || val]);
            } else {
                details.push([
                    f.label || name,
                    val !== null && val !== "" ? val : "-",
                ]);
            }
        });

        var detailsEl = document.getElementById("delete_details");
        detailsEl.replaceChildren();

        details.forEach(function (pair) {
            var item = document.createElement("li");
            var label = document.createElement("span");

            label.className = "fw-semibold";
            label.textContent = pair[0] + ":";

            item.appendChild(label);
            item.appendChild(document.createTextNode(" " + pair[1]));
            detailsEl.appendChild(item);
        });

        openModal("cmsDeleteModal");
    });

    // CREATE select2 init
    var createModal = document.getElementById("cmsCreateModal");
    if (createModal) {
        createModal.addEventListener("shown.bs.modal", function () {
            initSelect2(this);
        });
    }
})();

$(document).on("click", ".btn-view-violation-photo", function () {
    const url = $(this).data("url");

    $("#violationPhotoPreview").attr("src", "").addClass("d-none");
    $("#violationPhotoEmpty").addClass("d-none");
    $("#violationPhotoLoading").removeClass("d-none");

    $.ajax({
        url: url,
        type: "GET",
        success: function (response) {
            $("#violationPhotoLoading").addClass("d-none");

            if (response.status && response.photo_url) {
                $("#violationPhotoPreview")
                    .attr("src", response.photo_url)
                    .removeClass("d-none");
            } else {
                $("#violationPhotoEmpty").removeClass("d-none");
            }
        },
        error: function () {
            $("#violationPhotoLoading").addClass("d-none");
            $("#violationPhotoEmpty")
                .removeClass("d-none")
                .find(".text-muted")
                .text("Failed to load photo.");
        },
    });

    $("#violationPhotoModal").on("hidden.bs.modal", function () {
        $("#violationPhotoPreview").attr("src", "").addClass("d-none");
        $("#violationPhotoEmpty").addClass("d-none");
        $("#violationPhotoLoading").addClass("d-none");
    });
});
