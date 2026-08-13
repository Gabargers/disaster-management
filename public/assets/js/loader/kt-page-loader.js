(() => {
    "use strict";

    const el = document.getElementById("kt_page_loader");
    if (!el) return;

    const dots = document.getElementById("kt_loader_dots");
    const title = document.getElementById("kt_loader_title");
    const message = document.getElementById("kt_loader_message");
    let dotsTimer = null;
    let hideTimer = null;
    let suppressNavigationLoaderUntil = 0;

    const startDots = () => {
        if (!dots || dotsTimer) return;

        let n = 0;
        dotsTimer = window.setInterval(() => {
            n = (n + 1) % 4;
            dots.textContent = ".".repeat(n) || ".";
        }, 300);
    };

    const stopDots = () => {
        if (!dotsTimer) return;
        window.clearInterval(dotsTimer);
        dotsTimer = null;
    };

    const setCopy = (nextTitle, nextMessage) => {
        if (title) title.textContent = nextTitle || "Preparing your workspace";
        if (message) message.textContent = nextMessage || "Please wait";
    };

    const show = (options = {}) => {
        window.clearTimeout(hideTimer);
        setCopy(options.title, options.message);
        el.classList.remove("d-none");
        el.style.opacity = "1";
        el.style.display = "flex";
        startDots();
    };

    const hide = () => {
        el.style.opacity = "0";
        hideTimer = window.setTimeout(() => {
            el.classList.add("d-none");
            el.style.display = "none";
            setCopy();
        }, 380);
        stopDots();
    };

    const shouldSuppressNavigationLoader = () => {
        return Date.now() < suppressNavigationLoaderUntil;
    };

    const isModifiedClick = (event) => {
        return event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0;
    };

    const isSamePageAnchor = (link) => {
        if (!link.hash) return false;

        return link.pathname === window.location.pathname &&
            link.search === window.location.search &&
            link.origin === window.location.origin;
    };

    const shouldShowForLink = (link, event) => {
        if (!link || isModifiedClick(event)) return false;
        if (link.hasAttribute("data-no-page-loader")) return false;
        if (link.hasAttribute("download")) return false;
        if (link.target && link.target !== "_self") return false;

        const href = link.getAttribute("href") || "";

        if (href === "" || href === "#" || href.startsWith("#")) return false;
        if (/^(javascript:|mailto:|tel:)/i.test(href)) return false;
        if (isSamePageAnchor(link)) return false;

        return true;
    };

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", show, { once: true });
    } else {
        show();
    }

    window.addEventListener(
        "load",
        () => {
            window.setTimeout(hide, 150);
        },
        { once: true },
    );

    document.addEventListener("click", (event) => {
        const link = event.target.closest("a");

        if (!link) return;

        if (link.hasAttribute("data-no-page-loader")) {
            suppressNavigationLoaderUntil = Date.now() + 5000;
            window.setTimeout(hide, 300);
            return;
        }

        if (shouldShowForLink(link, event)) {
            show({ title: "Opening page", message: "Loading your requested content" });
        }
    });

    document.addEventListener("submit", (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.hasAttribute("data-no-page-loader")) return;
        if (!form.checkValidity()) return;

        window.setTimeout(() => {
            if (event.defaultPrevented) return;

            const submitter = event.submitter;
            if (submitter) {
                submitter.setAttribute("aria-disabled", "true");
                submitter.classList.add("disabled");
            }

            show({
                title: form.dataset.loaderTitle || "Processing request",
                message: form.dataset.loaderMessage || "Saving your changes securely",
            });
        });
    });

    window.addEventListener("beforeunload", () => {
        if (shouldSuppressNavigationLoader()) return;
        show({ title: "Almost there", message: "Completing navigation" });
    });

    window.addEventListener("pagehide", () => {
        if (shouldSuppressNavigationLoader()) return;
        show();
    });

    window.addEventListener("pageshow", (e) => {
        if (e.persisted) hide();
    });

    window.PageLoader = { show, hide, setCopy };
})();
