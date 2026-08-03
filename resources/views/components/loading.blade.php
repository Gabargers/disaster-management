<style>
    #kt_page_loader {
        --loader-blue: #214f9a;
        --loader-blue-deep: #102f68;
        --loader-blue-soft: #e9f1ff;
        --loader-red: #ed1c24;
        --loader-yellow: #ffc425;
        --loader-ink: #14284b;
        z-index: 2000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        overflow: hidden;
        isolation: isolate;
        background:
            radial-gradient(circle at 18% 16%, rgba(255, 196, 37, 0.22), transparent 22rem),
            radial-gradient(circle at 82% 82%, rgba(237, 28, 36, 0.12), transparent 25rem),
            linear-gradient(135deg, #f9fbff 0%, #edf4ff 48%, #f7f9fe 100%);
        transition: opacity 0.42s ease, visibility 0.42s ease;
    }

    #kt_page_loader::before {
        position: absolute;
        inset: 0;
        z-index: -2;
        content: "";
        opacity: 0.42;
        background-image:
            linear-gradient(rgba(33, 79, 154, 0.055) 1px, transparent 1px),
            linear-gradient(90deg, rgba(33, 79, 154, 0.055) 1px, transparent 1px);
        background-size: 3.5rem 3.5rem;
        mask-image: radial-gradient(circle at center, #000, transparent 72%);
    }

    #kt_page_loader::after {
        position: absolute;
        z-index: -1;
        width: min(55rem, 90vw);
        height: min(55rem, 90vw);
        content: "";
        border: 1px solid rgba(33, 79, 154, 0.08);
        border-radius: 50%;
        box-shadow:
            0 0 0 5rem rgba(33, 79, 154, 0.025),
            0 0 0 10rem rgba(33, 79, 154, 0.018);
        animation: loader-breathe 5s ease-in-out infinite;
    }

    .page-loader-panel {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        width: min(28rem, 100%);
        padding: 2.65rem 2.25rem 2.15rem;
        overflow: hidden;
        text-align: center;
        background: rgba(255, 255, 255, 0.78);
        border: 1px solid rgba(255, 255, 255, 0.96);
        border-radius: 2rem;
        box-shadow:
            0 1.8rem 5rem rgba(20, 40, 75, 0.14),
            0 0.25rem 1rem rgba(33, 79, 154, 0.07),
            inset 0 1px 0 #fff;
        backdrop-filter: blur(18px);
        animation: loader-panel-in 0.65s cubic-bezier(0.16, 1, 0.3, 1) both;
    }

    .page-loader-panel::before {
        position: absolute;
        top: 0;
        left: 12%;
        width: 76%;
        height: 3px;
        content: "";
        background: linear-gradient(90deg, transparent, var(--loader-blue) 22%, var(--loader-red) 54%, var(--loader-yellow) 78%, transparent);
        border-radius: 0 0 999px 999px;
        box-shadow: 0 2px 12px rgba(33, 79, 154, 0.22);
    }

    .page-loader-orbit {
        position: relative;
        display: grid;
        width: 11.25rem;
        height: 11.25rem;
        margin-bottom: 1.35rem;
        place-items: center;
    }

    .page-loader-orbit::before,
    .page-loader-orbit::after,
    .page-loader-ring {
        position: absolute;
        border-radius: 50%;
        content: "";
    }

    .page-loader-orbit::before {
        inset: 0;
        background: conic-gradient(
            from 35deg,
            var(--loader-blue) 0 20%,
            transparent 20% 35%,
            var(--loader-red) 35% 55%,
            transparent 55% 70%,
            var(--loader-yellow) 70% 90%,
            transparent 90%
        );
        mask: radial-gradient(farthest-side, transparent calc(100% - 3px), #000 calc(100% - 2px));
        filter: drop-shadow(0 3px 5px rgba(33, 79, 154, 0.2));
        animation: loader-spin 5.5s linear infinite;
    }

    .page-loader-orbit::after {
        inset: 1.05rem;
        border: 1px dashed rgba(33, 79, 154, 0.22);
        animation: loader-spin-reverse 11s linear infinite;
    }

    .page-loader-ring {
        inset: 0.58rem;
        border: 1px solid rgba(33, 79, 154, 0.1);
        box-shadow: inset 0 0 2rem rgba(33, 79, 154, 0.06);
    }

    .page-loader-dot {
        position: absolute;
        z-index: 3;
        width: 0.65rem;
        height: 0.65rem;
        border: 2px solid #fff;
        border-radius: 50%;
        box-shadow: 0 0 0 0.28rem rgba(33, 79, 154, 0.1), 0 0.25rem 0.7rem rgba(20, 40, 75, 0.22);
    }

    .page-loader-dot--blue {
        top: 0.18rem;
        left: calc(50% - 0.325rem);
        background: var(--loader-blue);
        animation: loader-dot-pulse 1.8s ease-in-out infinite;
    }

    .page-loader-dot--red {
        right: 0.82rem;
        bottom: 1.8rem;
        background: var(--loader-red);
        animation: loader-dot-pulse 1.8s 0.6s ease-in-out infinite;
    }

    .page-loader-dot--yellow {
        bottom: 1.8rem;
        left: 0.82rem;
        background: var(--loader-yellow);
        animation: loader-dot-pulse 1.8s 1.2s ease-in-out infinite;
    }

    .page-loader-logo-wrap {
        position: relative;
        z-index: 2;
        display: grid;
        width: 7.15rem;
        height: 7.15rem;
        padding: 0.48rem;
        overflow: hidden;
        background: #fff;
        border: 1px solid rgba(33, 79, 154, 0.12);
        border-radius: 50%;
        box-shadow:
            0 1rem 2.25rem rgba(33, 79, 154, 0.19),
            0 0 0 0.42rem rgba(255, 255, 255, 0.9);
        place-items: center;
        animation: loader-logo-float 2.8s ease-in-out infinite;
    }

    .page-loader-logo-wrap::after {
        position: absolute;
        top: -45%;
        left: -70%;
        width: 38%;
        height: 190%;
        content: "";
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.92), transparent);
        transform: rotate(18deg);
        animation: loader-shine 3.2s ease-in-out infinite;
    }

    #kt_loader_logo {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: contain;
        border-radius: 50%;
    }

    .page-loader-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.65rem;
        padding: 0.38rem 0.72rem;
        color: var(--loader-blue);
        background: var(--loader-blue-soft);
        border: 1px solid rgba(33, 79, 154, 0.1);
        border-radius: 999px;
        font-size: 0.64rem;
        font-weight: 800;
        letter-spacing: 0.15em;
        text-transform: uppercase;
    }

    .page-loader-live {
        width: 0.42rem;
        height: 0.42rem;
        background: var(--loader-red);
        border-radius: 50%;
        box-shadow: 0 0 0 rgba(237, 28, 36, 0.3);
        animation: loader-live 1.55s ease-out infinite;
    }

    .page-loader-title {
        margin-bottom: 0.4rem;
        color: var(--loader-ink);
        font-size: clamp(1.25rem, 4vw, 1.55rem);
        font-weight: 800;
        letter-spacing: -0.035em;
        line-height: 1.2;
    }

    .page-loader-title span {
        color: var(--loader-blue);
    }

    .page-loader-message {
        min-height: 1.35rem;
        margin-bottom: 1.45rem;
        color: #6b7890;
        font-size: 0.82rem;
        font-weight: 500;
    }

    .page-loader-progress {
        position: relative;
        width: min(16rem, 84%);
        height: 0.38rem;
        overflow: hidden;
        background: #e5ebf4;
        border-radius: 999px;
        box-shadow: inset 0 1px 2px rgba(20, 40, 75, 0.08);
    }

    .page-loader-progress::before {
        position: absolute;
        inset: 0;
        content: "";
        background: linear-gradient(90deg, var(--loader-blue) 0 42%, var(--loader-red) 62%, var(--loader-yellow) 100%);
        border-radius: inherit;
        transform: translateX(-105%);
        animation: loader-progress 1.75s cubic-bezier(0.65, 0, 0.35, 1) infinite;
    }

    .page-loader-progress::after {
        position: absolute;
        inset: 0;
        content: "";
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.9), transparent);
        transform: translateX(-100%);
        animation: loader-glint 1.75s 0.25s ease-in-out infinite;
    }

    .page-loader-footer {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        margin-top: 1rem;
        color: #8a96a9;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .page-loader-footer::before,
    .page-loader-footer::after {
        width: 0.25rem;
        height: 0.25rem;
        content: "";
        border-radius: 50%;
    }

    .page-loader-footer::before { background: var(--loader-blue); }
    .page-loader-footer::after { background: var(--loader-yellow); }

    @keyframes loader-panel-in {
        from { opacity: 0; transform: translateY(1rem) scale(0.96); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes loader-spin {
        to { transform: rotate(360deg); }
    }

    @keyframes loader-spin-reverse {
        to { transform: rotate(-360deg); }
    }

    @keyframes loader-breathe {
        0%, 100% { opacity: 0.65; transform: scale(0.94); }
        50% { opacity: 1; transform: scale(1.03); }
    }

    @keyframes loader-dot-pulse {
        0%, 100% { transform: scale(0.85); }
        50% { transform: scale(1.18); }
    }

    @keyframes loader-logo-float {
        0%, 100% { transform: translateY(0) scale(1); }
        50% { transform: translateY(-0.22rem) scale(1.015); }
    }

    @keyframes loader-shine {
        0%, 35% { left: -70%; }
        68%, 100% { left: 140%; }
    }

    @keyframes loader-live {
        0% { box-shadow: 0 0 0 0 rgba(237, 28, 36, 0.35); }
        75%, 100% { box-shadow: 0 0 0 0.42rem rgba(237, 28, 36, 0); }
    }

    @keyframes loader-progress {
        0% { transform: translateX(-105%) scaleX(0.55); }
        52% { transform: translateX(0) scaleX(0.78); }
        100% { transform: translateX(105%) scaleX(0.55); }
    }

    @keyframes loader-glint {
        0%, 20% { transform: translateX(-100%); }
        80%, 100% { transform: translateX(100%); }
    }

    @media (max-width: 480px) {
        #kt_page_loader { padding: 1rem; }
        .page-loader-panel { padding: 2.2rem 1.1rem 1.8rem; border-radius: 1.6rem; }
        .page-loader-orbit { margin-bottom: 1rem; transform: scale(0.9); }
    }

    @media (prefers-reduced-motion: reduce) {
        #kt_page_loader *,
        #kt_page_loader::before,
        #kt_page_loader::after {
            animation-duration: 5s !important;
        }

        .page-loader-panel {
            animation: none !important;
        }
    }
</style>

<div id="kt_page_loader" class="position-fixed top-0 start-0 w-100 h-100 d-none" role="status"
    aria-live="polite" aria-label="Loading page">
    <div class="page-loader-panel">
        <div class="page-loader-orbit" aria-hidden="true">
            <span class="page-loader-ring"></span>
            <span class="page-loader-dot page-loader-dot--blue"></span>
            <span class="page-loader-dot page-loader-dot--red"></span>
            <span class="page-loader-dot page-loader-dot--yellow"></span>
            <div class="page-loader-logo-wrap">
                <img id="kt_loader_logo" src="{{ asset('images/CSWDO.webp') }}"
                    alt="City Social Welfare and Development Office">
            </div>
        </div>

        <div class="page-loader-eyebrow">
            <span class="page-loader-live"></span>
            Response system active
        </div>
        <div class="page-loader-title"><span>Disaster</span> Management System</div>
        <div class="page-loader-message">
            Preparing your secure workspace<span id="kt_loader_dots">...</span>
        </div>

        <div class="page-loader-progress" aria-hidden="true"></div>
        <div class="page-loader-footer">CSWDO · City of Taguig</div>
    </div>
</div>
