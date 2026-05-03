@props([
    'url' => 'https://zalo.me/g/bh9hrrcujly5ab2pqmfj',
    'icon' => asset('image/zalo.svg'),
    'mobileOffset' => false,
    'label' => 'Liên hệ Zalo',
])

<a
    href="{{ $url }}"
    class="zalo-support-button {{ $mobileOffset ? 'zalo-support-button--mobile-offset' : '' }}"
    target="_blank"
    rel="noopener"
    aria-label="Liên hệ Zalo"
    title="Liên hệ Zalo"
>
    @if($label)
        <span class="zalo-support-button__label">{{ $label }}</span>
    @endif
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

    .zalo-support-button__label {
        position: absolute;
        bottom: calc(100% + 8px);
        left: 50%;
        transform: translateX(-50%);
        padding: 4px 10px;
        border-radius: 999px;
        background: #0f172a;
        color: #ffffff;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.2);
        pointer-events: none;
        opacity: 0;
        animation: zalo-label-pop 2.8s ease-in-out infinite;
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

        .zalo-support-button__label {
            font-size: 10px;
            padding: 3px 8px;
            bottom: calc(100% + 6px);
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

    @keyframes zalo-label-pop {
        0%,
        100% {
            opacity: 0;
            transform: translate(-50%, 6px) scale(0.96);
        }

        18%,
        82% {
            opacity: 1;
            transform: translate(-50%, 0) scale(1);
        }
    }
</style>
