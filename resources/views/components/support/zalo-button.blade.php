@props([
    'url' => 'https://zalo.me/g/bh9hrrcujly5ab2pqmfj',
    'icon' => asset('image/zalo.svg'),
    'mobileOffset' => false,
])

<a
    href="{{ $url }}"
    class="zalo-support-button {{ $mobileOffset ? 'zalo-support-button--mobile-offset' : '' }}"
    target="_blank"
    rel="noopener"
    aria-label="Ho tro qua Zalo"
    title="Ho tro qua Zalo"
>
    <img
        src="{{ $icon }}"
        class="zalo-support-button__icon"
        alt=""
        width="58"
        height="58"
        loading="lazy"
    >
</a>

<style>
    .zalo-support-button {
        position: fixed;
        right: 18px;
        bottom: 18px;
        z-index: 9990;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 58px;
        height: 58px;
        border: 0;
        background: transparent;
        text-decoration: none;
        animation: zalo-support-float 2.8s ease-in-out infinite;
        transition: filter 0.18s ease;
    }

    .zalo-support-button:hover {
        animation-play-state: paused;
        filter: brightness(1.05);
    }

    .zalo-support-button__icon {
        display: block;
        width: 58px;
        height: 58px;
        object-fit: contain;
    }

    @media (max-width: 768px) {
        .zalo-support-button {
            right: 14px;
            width: 54px;
            height: 54px;
        }

        .zalo-support-button__icon {
            width: 54px;
            height: 54px;
        }

        .zalo-support-button--mobile-offset {
            bottom: calc(84px + env(safe-area-inset-bottom, 0px));
        }
    }

    @keyframes zalo-support-float {
        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-8px);
        }
    }
</style>
