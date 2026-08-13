<style>
    #kt_page_loader {
        --loader-blue: #2154a3;
        --loader-red: #ed1c24;
        --loader-yellow: #fdbb18;
        z-index: 2000;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(247, 250, 255, 0.84);
        backdrop-filter: blur(7px) saturate(115%);
        -webkit-backdrop-filter: blur(7px) saturate(115%);
        transition: opacity 0.38s ease, visibility 0.38s ease;
    }

    [data-bs-theme="dark"] #kt_page_loader {
        background: rgba(16, 24, 40, 0.86);
    }

    .page-loader-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
        padding: 1.5rem;
        text-align: center;
    }

    .page-loader-orbit {
        position: relative;
        display: grid;
        width: 10.5rem;
        height: 10.5rem;
        place-items: center;
        filter: drop-shadow(0 0.5rem 0.7rem rgba(24, 56, 105, 0.12));
    }

    .page-loader-halo,
    .page-loader-guide,
    .page-loader-arcs,
    .page-loader-dot {
        position: absolute;
        border-radius: 50%;
    }

    .page-loader-halo {
        inset: 1.15rem;
        background: rgba(237, 244, 253, 0.72);
        border: 1px solid rgba(33, 84, 163, 0.08);
        box-shadow:
            inset 0 0 1.6rem rgba(33, 84, 163, 0.07),
            0 0 0 0.45rem rgba(255, 255, 255, 0.38);
    }

    .page-loader-guide {
        inset: 1.55rem;
        border: 1px dashed rgba(33, 84, 163, 0.18);
        animation: loader-spin-reverse 10s linear infinite;
    }

    .page-loader-arcs {
        inset: 0;
        background: conic-gradient(
            from -58deg,
            var(--loader-blue) 0deg 76deg,
            transparent 76deg 151deg,
            var(--loader-red) 151deg 220deg,
            transparent 220deg 265deg,
            var(--loader-yellow) 265deg 337deg,
            transparent 337deg 360deg
        );
        mask: radial-gradient(farthest-side, transparent calc(100% - 3px), #000 calc(100% - 2px));
        animation: loader-spin 4.8s linear infinite;
    }

    .page-loader-logo-wrap {
        position: relative;
        z-index: 2;
        display: grid;
        width: 5.65rem;
        height: 5.65rem;
        padding: 0.35rem;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.9);
        border: 2px solid rgba(237, 28, 36, 0.72);
        border-radius: 50%;
        box-shadow:
            0 0 0 0.22rem rgba(255, 255, 255, 0.85),
            0 0 0 0.34rem rgba(33, 84, 163, 0.5),
            0 0.55rem 1.4rem rgba(33, 84, 163, 0.2);
        place-items: center;
        animation: loader-logo-pulse 2.1s ease-in-out infinite;
    }

    #kt_loader_logo {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: contain;
        border-radius: 50%;
    }

    .page-loader-dot {
        z-index: 3;
        width: 0.62rem;
        height: 0.62rem;
        border: 2px solid rgba(255, 255, 255, 0.95);
        box-shadow: 0 0.2rem 0.5rem rgba(16, 42, 86, 0.28);
    }

    .page-loader-dot--blue {
        top: 0.08rem;
        left: 50%;
        background: var(--loader-blue);
        transform: translateX(-50%);
    }

    .page-loader-dot--red {
        right: 0.58rem;
        bottom: 1.65rem;
        background: var(--loader-red);
    }

    .page-loader-dot--yellow {
        bottom: 1.65rem;
        left: 0.58rem;
        background: var(--loader-yellow);
    }

    .page-loader-copy {
        min-width: 13rem;
    }

    .page-loader-title {
        margin: 0;
        color: #102a56;
        font-size: 1rem;
        font-weight: 700;
        letter-spacing: 0.01em;
    }

    [data-bs-theme="dark"] .page-loader-title {
        color: #f1f5ff;
    }

    .page-loader-subtitle {
        min-height: 1.3rem;
        margin-top: 0.3rem;
        color: #6b7890;
        font-size: 0.78rem;
    }

    .page-loader-progress {
        width: min(13rem, 65vw);
        height: 3px;
        margin-top: 0.75rem;
        overflow: hidden;
        background: rgba(33, 84, 163, 0.12);
        border-radius: 999px;
    }

    .page-loader-progress::after {
        display: block;
        width: 42%;
        height: 100%;
        content: "";
        background: linear-gradient(90deg, var(--loader-blue), var(--loader-red), var(--loader-yellow));
        border-radius: inherit;
        animation: loader-progress 1.35s ease-in-out infinite;
    }

    @keyframes loader-spin {
        to { transform: rotate(360deg); }
    }

    @keyframes loader-spin-reverse {
        to { transform: rotate(-360deg); }
    }

    @keyframes loader-logo-pulse {
        0%, 100% { transform: scale(0.98); }
        50% { transform: scale(1.025); }
    }

    @keyframes loader-progress {
        0% { transform: translateX(-110%); }
        100% { transform: translateX(350%); }
    }

    @media (max-width: 480px) {
        .page-loader-orbit {
            width: 9rem;
            height: 9rem;
        }

        .page-loader-logo-wrap {
            width: 4.9rem;
            height: 4.9rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .page-loader-arcs,
        .page-loader-guide,
        .page-loader-logo-wrap {
            animation: none;
        }

        .page-loader-progress::after { animation-duration: 3s; }
    }
</style>

<div id="kt_page_loader" class="position-fixed top-0 start-0 w-100 h-100 d-none" role="status"
    aria-live="polite" aria-busy="true" aria-label="Loading page">
    <div class="page-loader-content">
        <div class="page-loader-orbit" aria-hidden="true">
            <span class="page-loader-halo"></span>
            <span class="page-loader-guide"></span>
            <span class="page-loader-arcs"></span>
            <span class="page-loader-dot page-loader-dot--blue"></span>
            <span class="page-loader-dot page-loader-dot--red"></span>
            <span class="page-loader-dot page-loader-dot--yellow"></span>
            <div class="page-loader-logo-wrap">
                <img id="kt_loader_logo" src="{{ asset('images/CSWDO.webp') }}"
                    alt="City Social Welfare and Development Office">
            </div>
        </div>
        <div class="page-loader-copy">
            <p id="kt_loader_title" class="page-loader-title">Preparing your workspace</p>
            <div class="page-loader-subtitle"><span id="kt_loader_message">Please wait</span><span id="kt_loader_dots">.</span></div>
            <div class="page-loader-progress" aria-hidden="true"></div>
        </div>
    </div>
</div>
