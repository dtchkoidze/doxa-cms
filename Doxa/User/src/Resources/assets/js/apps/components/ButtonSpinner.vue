<template>
    <svg
        class="auth-button-spinner"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        aria-hidden="true"
    >
        <circle
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="3.5"
            opacity="0.25"
        />
        <path
            fill="currentColor"
            d="M12 2a10 10 0 0 1 10 10h-3.5A6.5 6.5 0 0 0 12 5.5V2z"
        />
    </svg>
</template>

<style scoped>
.auth-button-spinner {
    width: 1rem;
    height: 1rem;
    margin-left: 0.5rem;
    flex-shrink: 0;
    animation: auth-button-spin 0.7s linear infinite;
}

@keyframes auth-button-spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
