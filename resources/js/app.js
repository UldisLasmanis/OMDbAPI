document.querySelectorAll('[data-movie-poster]').forEach((poster) => {
    const placeholder = poster.parentElement?.querySelector(
        '[data-poster-placeholder]',
    );

    if (!placeholder) {
        return;
    }

    const showPlaceholder = () => {
        poster.hidden = true;
        placeholder.hidden = false;
    };

    poster.addEventListener('error', showPlaceholder, { once: true });

    if (poster.complete && poster.naturalWidth === 0) {
        showPlaceholder();
    }
});
