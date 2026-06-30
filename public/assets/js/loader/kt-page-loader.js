(() => {
    "use strict";

    const el = document.getElementById("kt_page_loader");
    if (!el) return;

    const dots = document.getElementById("kt_loader_dots");
    const logo = document.getElementById("kt_loader_logo");

    let dotsTimer = null;
    let spinAnim = null;
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

    const startSpin = () => {
        if (!logo || spinAnim) return;

        try {
            spinAnim = logo.animate(
                [
                    { transform: "rotate(0deg)" },
                    { transform: "rotate(360deg)" },
                ],
                { duration: 2500, iterations: Infinity, easing: "linear" },
            );
        } catch (e) {}
    };

    const stopSpin = () => {
        if (!spinAnim) return;
        spinAnim.cancel();
        spinAnim = null;
    };

    const show = () => {
        el.classList.remove("d-none");
        el.style.opacity = "1";
        el.style.display = "flex";
        startSpin();
        startDots();
    };

    const hide = () => {
        el.style.opacity = "0";
        setTimeout(() => {
            el.classList.add("d-none");
            el.style.display = "none";
        }, 200);
        stopSpin();
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

    if (logo) {
        logo.addEventListener(
            "error",
            () => {
                logo.style.display = "none";
            },
            { once: true },
        );
    }

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
            show();
        }
    });

    window.addEventListener("beforeunload", () => {
        if (shouldSuppressNavigationLoader()) return;
        show();
    });

    window.addEventListener("pagehide", () => {
        if (shouldSuppressNavigationLoader()) return;
        show();
    });

    window.addEventListener("pageshow", (e) => {
        if (e.persisted) hide();
    });
})();
